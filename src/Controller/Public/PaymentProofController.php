<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Entity\AdminActivityLog;
use App\Entity\PaymentProof;
use App\Entity\Reservation;
use App\Form\PaymentProofType;
use App\Service\FileUploadService;
use App\Service\MailService;
use App\Service\ReservationService;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PaymentProofController extends AbstractController
{
    #[Route('/reservation/{uuid}/preuve-virement', name: 'reservation_payment_proof', methods: ['GET','POST'])]
    public function upload(
        string $uuid,
        Request $request,
        FileUploadService $uploader,
        ReservationService $reservationService,
        EntityManagerInterface $em,
        string $uploadDirProofs,
        EntityManagerInterface $entityManager,
        MailService $mailService
    ): Response {
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(PaymentProofType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
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

        return $this->render('reservation/payment_proof.html.twig', [
            'reservation' => $reservation,
            'form'        => $form,
        ]);
    }

    #[Route('/reservation/{uuid}/payment-by-virement', name: 'reservation_payment_by_rib', methods: ['GET','POST'])]
    public function paymentByRib(
        string $uuid,
        EntityManagerInterface $entityManager,
        SettingService $settings,
    ): Response {
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }
        return $this->render('reservation/payment_by_rib.html.twig', [
            'reservation' => $reservation,
            'settings'    => $settings->all(),
        ]);
    }

    #[Route('/proof/{id}/download', name: 'public_payment_proof_download', methods: ['GET'])]
    public function download(PaymentProof $proof, string $uploadDirContrat, AdminActivityLog $logger): Response
    {
        $path = $uploadDirContrat.'/'.$proof->getStoredFilename();
        if (!is_file($path)) {
            throw new NotFoundHttpException('Fichier introuvable.');
        }
        //$logger->log('payment_proof.download', 'PaymentProof', $proof->getId());
        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $proof->getOriginalFilename() ?: 'preuve.pdf'
        );
        $response->headers->set('Content-Type', $proof->getMimeType() ?: 'application/octet-stream');
        return $response;
    }
}
