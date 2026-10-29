<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Entity\RefundRequest;
use App\Entity\Reservation;
use App\Form\RefundRequestType;
use App\Repository\ReservationRepository;
use App\Service\RefundIbanEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class RefundController extends AbstractController
{
    #[Route('/remboursement/{uuid}', name: 'refund_request', methods: ['GET','POST'])]
    public function request(
        string $uuid,
        Request $request,
        ReservationRepository $reservations,
        RefundIbanEncryptionService $ibanCrypto,
        EntityManagerInterface $em,
    ): Response {
        $reservation = $em->getRepository(Reservation::class)->findOneBy(['uuid' => $uuid]);
        if ($reservation === null) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(RefundRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $normalized = $ibanCrypto->normalizeIban((string) $form->get('iban')->getData());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('refund_request', ['uuid' => $uuid]);
            }

            $refund = new RefundRequest();
            $refund->setReservation($reservation)
                   ->setReason((string) $form->get('reason')->getData())
                   ->setCustomerComment($form->get('customerComment')->getData())
                   ->setEncryptedIban($ibanCrypto->encrypt($normalized))
                   ->setIbanLastFour($ibanCrypto->lastFour($normalized));

            $em->persist($refund);
            $em->flush();

            $this->addFlash('success', 'Votre demande de remboursement a bien été reçue.');
            return $this->redirectToRoute('reservation_confirmation', ['uuid' => $uuid]);
        }

        return $this->render('reservation/refund.html.twig', [
            'reservation' => $reservation,
            'form'        => $form,
        ]);
    }
}
