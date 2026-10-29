<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\RefundRequest;
use App\Enum\RefundStatus;
use App\Repository\RefundRequestRepository;
use App\Service\AdminActivityLogger;
use App\Service\RefundIbanEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/refunds')]
class RefundController extends AbstractController
{
    #[Route('', name: 'admin_refund_index', methods: ['GET'])]
    public function index(RefundRequestRepository $repo): Response
    {
        return $this->render('admin/refund/index.html.twig', [
            'refunds' => $repo->findBy([], ['requestedAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/reveal-iban', name: 'admin_refund_reveal_iban', methods: ['POST'])]
    public function revealIban(
        RefundRequest $refund,
        Request $request,
        RefundIbanEncryptionService $crypto,
        AdminActivityLogger $logger,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('refund_reveal_'.$refund->getId(), (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'invalid_csrf'], 403);
        }
        try {
            $iban = $crypto->decrypt($refund->getEncryptedIban());
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'decrypt_failed'], 500);
        }
        $logger->log('refund.iban_revealed', 'RefundRequest', $refund->getId());
        return new JsonResponse(['iban' => $iban]);
    }

    #[Route('/{id}/status', name: 'admin_refund_status', methods: ['POST'])]
    public function changeStatus(
        RefundRequest $refund,
        Request $request,
        EntityManagerInterface $em,
        AdminActivityLogger $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('refund_status_'.$refund->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_refund_index');
        }
        $newStatus = RefundStatus::tryFrom((string) $request->request->get('status', ''));
        if ($newStatus === null) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_refund_index');
        }
        $refund->setStatus($newStatus);
        $refund->setAdminNote($request->request->get('note') ?: $refund->getAdminNote());
        if ($newStatus === RefundStatus::REFUNDED || $newStatus === RefundStatus::REJECTED) {
            $refund->setProcessedAt(new \DateTimeImmutable());
        }
        $em->flush();
        $logger->log('refund.status_change', 'RefundRequest', $refund->getId(), ['status' => $newStatus->value]);
        $this->addFlash('success', 'Statut mis à jour.');
        return $this->redirectToRoute('admin_refund_index');
    }
}
