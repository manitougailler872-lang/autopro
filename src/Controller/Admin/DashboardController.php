<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\ProviderType;
use App\Enum\ReservationStatus;
use App\Enum\ReviewStatus;
use App\Repository\PaymentProofRepository;
use App\Repository\RefundRequestRepository;
use App\Repository\ReservationRepository;
use App\Repository\ReviewRepository;
use App\Repository\SmsProviderRepository;
use App\Repository\VehicleRepository;
use App\Service\ReservationNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicles,
        ReservationRepository $reservations,
        PaymentProofRepository $proofs,
        RefundRequestRepository $refunds,
        ReviewRepository $reviews,
        HubInterface $hub,
        SmsProviderRepository $smsProviderRepository
    ): Response {
        $stats = [
            'total_vehicles'      => $vehicles->count([]),
            'published_vehicles'  => $vehicles->count(['published' => true]),
            'pending'             => $reservations->countByStatus(ReservationStatus::PENDING_PAYMENT)
                                    + $reservations->countByStatus(ReservationStatus::PAYMENT_PROOF_RECEIVED)
                                    + $reservations->countByStatus(ReservationStatus::UNDER_REVIEW),
            'confirmed'           => $reservations->countByStatus(ReservationStatus::CONFIRMED),
            'proofs_received'     => $proofs->count([]),
            'refunds'             => $refunds->count([]),
            'reviews_pending'     => $reviews->countByStatus(ReviewStatus::PENDING),
        ];
        $findCredit = $smsProviderRepository->findOneBy(['provider' => ProviderType::BREVO]);
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'credit' => $findCredit->getCredit(),
            'mercureUrl' => $hub->getPublicUrl(),
            'recent_reservations' => $reservations->findBy([], ['createdAt' => 'DESC'], 8),
        ]);
    }
}
