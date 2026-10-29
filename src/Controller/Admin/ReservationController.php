<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use App\Service\AdminActivityLogger;
use App\Service\MailService;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/reservations')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'admin_reservation_index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $repo): Response
    {
        $status = $request->query->get('status');
        $q      = trim((string) $request->query->get('q', ''));

        $qb = $repo->createQueryBuilder('r')
            ->leftJoin('r.vehicle', 'v')
            ->addSelect('v')
            ->orderBy('r.createdAt', 'DESC');

        if ($status !== null && $status !== '' && ($s = ReservationStatus::tryFrom((string) $status)) !== null) {
            $qb->andWhere('r.status = :s')->setParameter('s', $s);
        }
        if ($q !== '') {
            $qb->andWhere('LOWER(r.customerName) LIKE :q OR LOWER(r.customerEmail) LIKE :q OR r.reservationNumber LIKE :q')
               ->setParameter('q', '%'.strtolower($q).'%');
        }

        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $qb->getQuery()->getResult(),
            'status'       => $status,
            'q'            => $q,
        ]);
    }

    #[Route('/{id}', name: 'admin_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('admin/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }
    #[Route('/{id}/confirm', name: 'admin_reservation_confirm', methods: ['POST'])]
    public function confirm(
        Reservation $reservation,
        Request $request,
        ReservationService $reservations,
        MailService $mailer,
        AdminActivityLogger $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('reservation_confirm_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
        }

        $reservations->confirm($reservation);
        $mailer->sendReservationConfirmation($reservation);
        $logger->log('reservation.confirm', 'Reservation', $reservation->getId());

        $this->addFlash('success', 'Réservation confirmée et bon envoyé par e-mail.');
        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
    }

    #[Route('/{id}/confirm-payment', name: 'admin_reservation_confirm_payment', methods: ['POST'])]
    public function confirmPayment(
        Reservation $reservation,
        Request $request,
        ReservationService $reservations,
        AdminActivityLogger $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('reservation_confirm_payment_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
        }

        $reservations->confirmPayment($reservation);
        //$mailer->sendReservationConfirmation($reservation);
        $logger->log('reservation.confirm', 'Reservation', $reservation->getId());
        $this->addFlash('success', 'Paiement confirmé ');
        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
    }

    #[Route('/{id}/cancel-payment', name: 'admin_reservation_cancel_payment', methods: ['POST'])]
    public function cancelPayment(
        Reservation $reservation,
        Request $request,
        ReservationService $reservations,
        AdminActivityLogger $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('reservation_cancel_payment_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
        }
        $reservations->cancelPayment($reservation);
        $logger->log('reservation.confirm', 'Reservation', $reservation->getId());
        $this->addFlash('success', 'Paiement annulé .');
        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
    }

    #[Route('/{id}/cancel', name: 'admin_reservation_cancel', methods: ['POST'])]
    public function cancel(Reservation $reservation, Request $request, ReservationService $reservations, AdminActivityLogger $logger): Response
    {
        if (!$this->isCsrfTokenValid('reservation_cancel_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
        }
        $reservations->cancel($reservation);
        $logger->log('reservation.cancel', 'Reservation', $reservation->getId());
        $this->addFlash('success', 'Réservation annulée.');
        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
    }

    #[Route('/{id}/resend', name: 'admin_reservation_resend', methods: ['POST'])]
    public function resend(Reservation $reservation, Request $request, MailService $mailer, AdminActivityLogger $logger): Response
    {
        if (!$this->isCsrfTokenValid('reservation_resend_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
        }
        $mailer->resendReservationConfirmation($reservation);
        $logger->log('reservation.email_resent', 'Reservation', $reservation->getId());
        $this->addFlash('success', 'Bon renvoyé par e-mail.');
        return $this->redirectToRoute('admin_reservation_show', ['id' => $reservation->getId()]);
    }
}
