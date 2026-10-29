<?php
// src/Service/Payment/MolliePaymentService.php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Reservation;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\PaymentStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class MolliePaymentService
{
    private MollieApiClient $mollie;

    public function __construct(
        private readonly string $mollieApiKey,
        private readonly RouterInterface $router,
        private readonly EntityManagerInterface $em,
        private readonly PaymentRepository $paymentRepository,
        private readonly LoggerInterface $logger,
    ) {
        $this->mollie = new MollieApiClient();
        $this->mollie->setApiKey($this->mollieApiKey);
    }

    public function createPaymentForReservation(Reservation $reservation): Payment
    {
        $amount = number_format((float) $reservation->getReservationFeeAmount(), 2, '.', '');

        try {
            $molliePayment = $this->mollie->payments->create([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => $amount,
                ],
                'description' => sprintf(
                    'Acompte réservation pour %s %s', $reservation->getVehicle()->getBrand(),
                    $reservation->getVehicle()->getModel() 
                ),
                'redirectUrl' => $this->router->generate(
                    'reservation_payment_return',
                    ['uuid' => $reservation->getUuid()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'webhookUrl' => 'https://luxecar-automobile-reservation.fr/mollie/payment',
                'locale' => 'ca_ES',
                'lines' => [
                    [
                        'type' => 'physical',
                        'imageUrl' => 'https://luxecar-automobile-reservation.fr/uploads/vehicles/01a00494-c759-7590-8976-b2e0c51da8ac.jpg',
                        'quantity' => 1,
                        'sku' => $reservation->getVehicle()->getBrand(),
                        'unitPrice' => [
                            'currency' => 'EUR',
                            'value' => $amount,
                        ],
                        'totalAmount' => [
                            'currency' => 'EUR',
                            'value' => $amount,
                        ],
                        'billingAddress' => [
                            'email' => $reservation->getCustomerEmail(),
                            'familyName' => $reservation->getCustomerName(),
                            'phone' => $reservation->getCustomerPhone(),
                        ],
                        'description' => $reservation->getVehicle()->getBrand() . ' '. $reservation->getVehicle()->getModel()
                    ]
                ],
                'metadata' => [
                    'reservationReference' => (string) $reservation->getReservationNumber(),
                ],
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Erreur création paiement Mollie', [
                'message' => $e->getMessage(),
                'reservation' => (string) $reservation->getReservationNumber(),
            ]);
            throw new Exception('Impossible de créer le paiement Mollie.', previous: $e);
        }

        $payment = new Payment($molliePayment->id, $amount, 'EUR', $molliePayment->description);
        $payment->setReservation($reservation);
        //$payment->setMethod('mollie');

        $this->em->persist($payment);
        $this->em->flush();

        return $payment;
    }

    public function getCheckoutUrl(string $molliePaymentId): ?string
    {
        return $this->mollie->payments->get($molliePaymentId)->getCheckoutUrl();
    }

    /**
     * Appelé uniquement depuis le webhook. Ré-interroge Mollie (jamais confiance
     * au payload webhook) et déclenche le flux d'escrow existant si payé.
     */
    public function syncPaymentStatus(string $molliePaymentId): Payment
    {
        $molliePayment = $this->mollie->payments->get($molliePaymentId);

        $payment = $this->paymentRepository->findOneBy(['molliePaymentId' => $molliePaymentId]);
        if (!$payment) {
            throw new \RuntimeException("Paiement local introuvable pour {$molliePaymentId}");
        }

        $previousStatus = $payment->getStatus();
        $payment->setStatus($molliePayment->status);
        $this->em->flush();

        /* if ($molliePayment->status === PaymentStatus:: && $previousStatus !== 'paid') {
            $this->logger->info('Paiement Mollie confirmé', [
                'reservation' => (string) $payment->getReservation()->getReference(),
            ]);

            // Réutilise exactement le même déclencheur que la validation manuelle
            // de justificatif de virement, pour ne pas dupliquer la logique métier.
            $this->escrowService->releaseFromOnlinePayment($payment->getReservation(), $payment); 
        } */

        return $payment;
    }

    public function releaseFromOnlinePayment(Reservation $reservation, Payment $payment): void
    {
       /*  // Réutilise ta logique existante de mise à jour du statut de réservation,
        // envoi d'e-mail de confirmation, etc. — sans passer par la validation
        // manuelle du justificatif puisque Mollie garantit déjà le paiement.
        $reservation->setStatut(ReservationStatus::CONFIRMEE);
        $reservation->setModePaiement('mollie');
        $this->em->flush();

        $this->mailer->envoyerConfirmationReservation($reservation); // ton service email existant */
    }
}