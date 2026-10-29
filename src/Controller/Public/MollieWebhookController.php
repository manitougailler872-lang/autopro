<?php

namespace App\Controller\Public;

use App\Service\MolliePaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class MollieWebhookController extends AbstractController
{
    public function __construct(
        private readonly MolliePaymentService $paymentService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/mollie/webhook', name: 'mollie_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $paymentId = $request->request->get('id');
        if (!$paymentId) {
            return new Response('Missing payment id', Response::HTTP_BAD_REQUEST);
        }
        try {
            $this->paymentService->syncPaymentStatus($paymentId);
        } catch (\Throwable $e) {
            $this->logger->error('Échec traitement webhook Mollie', [
                'paymentId' => $paymentId,
                'error' => $e->getMessage(),
            ]);
        }
        return new Response('OK', Response::HTTP_OK);
    }
}