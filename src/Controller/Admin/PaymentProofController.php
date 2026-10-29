<?php

declare(strict_types=1);
namespace App\Controller\Admin;

use App\Entity\PaymentProof;
use App\Repository\PaymentProofRepository;
use App\Service\AdminActivityLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/payment-proofs')]
class PaymentProofController extends AbstractController
{
    #[Route('', name: 'admin_payment_proof_index', methods: ['GET'])]
    public function index(PaymentProofRepository $repo): Response
    {
        return $this->render('admin/payment_proof/index.html.twig', [
            'proofs' => $repo->findBy([], ['uploadedAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/download', name: 'admin_payment_proof_download', methods: ['GET'])]
    public function download(PaymentProof $proof, string $uploadDirProofs, AdminActivityLogger $logger): Response
    {
        $path = $uploadDirProofs.'/'.$proof->getStoredFilename();
        if (!is_file($path)) {
            throw new NotFoundHttpException('Fichier introuvable.');
        }
        $logger->log('payment_proof.download', 'PaymentProof', $proof->getId());

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $proof->getOriginalFilename() ?: 'preuve.pdf'
        );
        $response->headers->set('Content-Type', $proof->getMimeType() ?: 'application/octet-stream');
        return $response;
    }
}
