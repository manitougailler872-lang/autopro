<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Entity\PaymentProof;
use App\Entity\Reservation;
use App\Entity\Review;
use App\Enum\ReservationStatus;
use App\Form\PaymentProofType;
use App\Form\ReservationType;
use App\Form\ReviewType;
use App\Repository\ReservationRepository;
use App\Repository\VehicleRepository;
use App\Service\FileUploadService;
use App\Service\MailService;
use App\Service\ReservationService;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\MolliePaymentService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ReservationController extends AbstractController
{
    /**
     * Handles the reservation form POST submitted from the vehicle detail page.
     * GET requests just redirect to the vehicle detail page (where the form lives).
     */
    #[Route('/reservation/{vehicleSlug}', name: 'reservation_new', requirements: ['vehicleSlug' => '[a-z0-9-]+'], methods: ['GET','POST'])]
    public function new(
        string $vehicleSlug,
        Request $request,
        VehicleRepository $vehicles,
        ReservationService $reservationService,
        RateLimiterFactory $publicFormLimiter,
        MailService $mailService
    ): Response {
        $vehicle = $vehicles->findOnePublishedBySlug($vehicleSlug);
        if ($vehicle === null) {
            throw new NotFoundHttpException('Véhicule introuvable.');
        }

        // GET → send them back to the vehicle detail page where the form is embedded
        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('vehicle_detail', ['slug' => $vehicleSlug]);
        }

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $reviewForm  = $this->createForm(ReviewType::class, new Review());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $publicFormLimiter->create($request->getClientIp() ?? 'anon');
            if (!$limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException(60, 'Trop de tentatives. Réessayez plus tard.');
            }

            $reservationService->create($reservation, $vehicle);
            $mailService->sendNewReversationNotification($reservation);
            $this->addFlash('success', 'Demande de réservation enregistrée. Numéro : '.$reservation->getReservationNumber());
            return $this->redirectToRoute('reservation_payment', ['uuid' => (string) $reservation->getUuid()]);
        }
        // Form submitted but invalid → re-render the vehicle detail page with the form errors
        return $this->render('vehicle/detail.html.twig', [
            'vehicle' => $vehicle,
            'form'    => $form,
            'review_form'      => $reviewForm,
        ]);
    }
    
    #[Route('/reservation/{uuid}/pay-by-card-not-available', name: 'payment_by_card_not_available', methods: ['GET','POST'])]
    public function noPaymentPayByCard(string $uuid, EntityManagerInterface $em): Response
    {
        $reservation = $em->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }
        return $this->render('reservation/payment_by_card_not_available.html.twig',[
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/{uuid}/leboncoin', name: 'payment_by_leboncoin', methods: ['GET','POST'])]
    public function leboncoin(string $uuid,  
        Request $request,
        FileUploadService $uploader,
        ReservationService $reservationService,
        EntityManagerInterface $em,
        string $uploadDirProofs,
        MailService $mailService): Response
    {
        $reservation = $em->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }
         $form = $this->createForm(PaymentProofType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file   = $form->get('file')->getData();
            $result = $uploader->upload(
                $file,
                $uploadDirProofs,
                FileUploadService::PROOF_MIME_TYPES,
                FileUploadService::PROOF_MAX_SIZE,
            );
            $proof = new PaymentProof();
            $proof->setReservation($reservation)
                  ->setOriginalFilename($result['originalName'])
                  ->setStoredFilename($result['filename'])
                  ->setMimeType($result['mimeType'])
                  ->setFileSize($result['size']);
            $em->persist($proof);
            $em->flush();
            $reservationService->markProofReceived($reservation);
            $mailService->sendProofPayementNotification($reservation,$proof);
            $this->addFlash('success', 'Preuve de virement envoyée. Nous la vérifierons rapidement.');
            return $this->redirectToRoute('reservation_confirmation', ['uuid' => $uuid]);
        }
        return $this->render('reservation/payment_by_leboncoin.html.twig',[
            'reservation' => $reservation,
            'form'        => $form,
        ]);
    }

    #[Route('/reservation/{uuid}/paiement', name: 'reservation_payment', methods: ['GET'])]
    public function payment(string $uuid, EntityManagerInterface $entityManager, ReservationRepository $repo, SettingService $settings): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }
        return $this->render('reservation/payment.html.twig', [
            'reservation' => $reservation,
            'settings'    => $settings->all(),
        ]);
    }

    #[Route('/reservation/{uuid}/confirmation', name: 'reservation_confirmation', methods: ['GET'])]
    public function confirmation(string $uuid, EntityManagerInterface $entityManager, ReservationRepository $repo): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }
        return $this->render('reservation/confirmation.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * "Votre demande" — escrow release flow.
     * GET  → shows the 3-step release page (or redirects to no-payment if nothing to release)
     * POST → marks the reservation as FUNDS_RELEASED and redirects to the empty-state page
     */
    #[Route('/reservation/{uuid}/liberation-fonds', name: 'reservation_release_funds', methods: ['GET','POST'])]
    public function releaseFunds(
        string $uuid,
        Request $request,
        ReservationRepository $repo,
        EntityManagerInterface $em,
    ): Response {
        $reservation = $em->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }

        // Only a CONFIRMED reservation can trigger a release
        if (!$reservation->getStatus()->isAwaitingCustomerRelease()) {
            return $this->redirectToRoute('reservation_no_payment',['uuid' => $uuid]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('release_funds_'.$reservation->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('reservation_release_funds', ['uuid' => $uuid]);
            }

            $reservation->setStatus(ReservationStatus::FUNDS_RELEASED);
            $em->flush();

            $this->addFlash('success', 'Les fonds ont été libérés au vendeur.');
            return $this->redirectToRoute('reservation_no_payment');
        }

        return $this->render('reservation/release_funds.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * Empty state — "Vous n'avez aucun paiement en cours".
     */
    #[Route('/reservation/{uuid}/fonds-libérés', name: 'reservation_no_payment', methods: ['GET','POST'])]
    public function noPaymentInProgress(string $uuid, EntityManagerInterface $em): Response
    {
        $reservation = $em->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }
        return $this->render('reservation/no_payment.html.twig',[
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/{uuid}/payer', name: 'reservation_pay', methods: ['POST','GET'])]
    public function pay(string $uuid,EntityManagerInterface $entityManager,MolliePaymentService $paymentService): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }
        // try {
        //     $payment = $paymentService->createPaymentForReservation($reservation);
        // } catch (\Exception $e) {
        //     $this->addFlash('error', 'Le paiement n\'a pas pu être initié. Réessaie dans quelques instants.');
        //     return $this->redirectToRoute('reservation_confirmation', ['uuid' => $uuid]);
        // }
        return $this->render('reservation/payment_by_card.html.twig',[
            'reservation' => $reservation
        ]);
        // return $this->redirect(
        //     $paymentService->getCheckoutUrl($payment->getMolliePaymentId())
        // );
    }

    #[Route('/reservation/{uuid}/paiement/back', name: 'reservation_payment_return', methods: ['GET'])]
    public function paymentReturn(string $uuid,EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }
        // Le statut définitif arrive par webhook, pas ici — affiche "vérification en cours"
        $this->addFlash('info', 'Ton paiement est en cours de vérification.');
        return $this->redirectToRoute('reservation_confirmation', ['uuid' => $uuid]);
    }

    #[Route('/contrat/{uuid}/download', name: 'public_contrat_download', methods: ['GET'])]
    public function downloadContrat(Reservation $reservation, string $uploadDirContrat): Response
    {
        $path = $uploadDirContrat.'/'.$reservation->getContrat();
        if (!is_file($path)) {
            throw new NotFoundHttpException('Fichier introuvable.');
        }
        //$logger->log('payment_proof.download', 'PaymentProof', $proof->getId());
        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $reservation->getContrat() ?: 'contrat.pdf'
        );
        $response->headers->set('Content-Type', '.pdf' ?: 'application/octet-stream');
        return $response;
    }
}
