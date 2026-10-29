<?php
// src/Service/Payment/MolliePaymentService.php

namespace App\Service;

use App\Entity\PaymentProof;
use App\Entity\Reservation;
use App\Repository\PushSubscriptionRepository;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;


class PushTokenService {

    public function __construct(private readonly PushSubscriptionRepository $pushSubscriptionRepo)
    { }

    private function createToken() : array{
        $pushSubscription = $this->pushSubscriptionRepo->findOneBy(['expired' => false]);
        if(empty($findSubscription)){
            throw new \Exception('Subscription non géré');
        }
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'contact@luxecar-automobile-reservation.fr',
                'publicKey' => $_ENV['VAPID_PUBLIC_KEY'],
                'privateKey' => $_ENV['VAPID_PRIVATE_KEY'],
            ],
        ]);
        $subscription = Subscription::create([
            'endpoint' => $pushSubscription->getEndpoint(),
            'publicKey' => $pushSubscription->getPublicKey(),
            'authToken' => $pushSubscription->getAuthToken(),
        ]);
        return [$webPush, $subscription];
    }

    public function sendReservationPushToken(Reservation $reservation){
        
        list($webPush, $subscription) = $this->createToken();
        $message = sprintf('%s a fait une demande de réservation pour %s %s (%s)', $reservation->getCustomerName(), 
        $reservation->getVehicle()->getBrand(), $reservation->getVehicle()->getModel() ?? '',
        $reservation->getVehicle()->getPrice().'€');
        $webPush->queueNotification(
            $subscription,
            json_encode([
                'title' => 'Nouvelle réservation',
                'body' => $message,
                'url' => '/messages',
            ])
        );
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                // Succès
            } elseif ($report->isSubscriptionExpired()) {
                // Subscription expirée → suppression en BDD
            } else {
                // Erreur temporaire/autre erreur
            }
        }
    }

    public function sendProovePushToken(PaymentProof $proof) :void {
        list($webPush, $subscription) = $this->createToken();
        $message = sprintf('%s a envoyé la preuve de paiement pour %s %s (%s)', $proof->getReservation()->getCustomerName(), 
        $proof->getReservation()->getVehicle()->getBrand(), $proof->getReservation()->getVehicle()->getModel() ?? '',
        $proof->getReservation()->getVehicle()->getPrice().'€');
        $webPush->queueNotification(
            $subscription,
            json_encode([
                'title' => 'Nouvelle preuve de paiement',
                'body' => $message,
                'url' => '/messages',
            ])
        );
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                // Succès
            } elseif ($report->isSubscriptionExpired()) {
                // Subscription expirée → suppression en BDD
            } else {
                // Erreur temporaire/autre erreur
            }
        }
    }
}
