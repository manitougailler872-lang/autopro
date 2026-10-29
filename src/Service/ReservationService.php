<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Enum\ReceptionMode;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;

class ReservationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ReservationNumberGenerator $numberGenerator,
        private readonly SettingService $settings,
    ) {}

    /**
     * Creates the reservation with server-side fee calculation.
     * NEVER trust a client-supplied amount.
     */
    public function create(Reservation $reservation, Vehicle $vehicle): Reservation
    {
        // Snapshot the vehicle price so later price changes don't affect this reservation
        $vehiclePrice = $vehicle->getPriceAsFloat();
        $percentage   = $this->settings->getFloat(SettingService::RESERVATION_PERCENTAGE, 20.0);
        $feeAmount    = round($vehiclePrice * ($percentage / 100.0), 2);

        $reservation
            ->setVehicle($vehicle)
            ->setVehiclePriceSnapshot(number_format($vehiclePrice, 2, '.', ''))
            ->setReservationFeePercentage(number_format($percentage, 2, '.', ''))
            ->setReservationFeeAmount(number_format($feeAmount, 2, '.', ''))
            ->setPaymentMethod(PaymentMethod::BANK_TRANSFER)
            ->setStatus(ReservationStatus::PENDING_PAYMENT)
            ->setPaymentStatus(PaymentStatus::PENDING)
            ->setReservationNumber($this->numberGenerator->generate());

        // Clear irrelevant fields depending on reception mode (server-side sanitization)
        if ($reservation->getReceptionMode() === ReceptionMode::PICKUP) {
            $reservation->setDeliveryAddress(null)->setDeliveryCity(null)->setDeliveryPostalCode(null);
        } else {
            $reservation->setPickupFirstName(null)->setPickupLastName(null)->setPickupDate(null)->setPickupTime(null);
        }

        $this->em->persist($reservation);
        $this->em->flush();

        return $reservation;
    }

    public function markProofReceived(Reservation $reservation): void
    {
        if ($reservation->getStatus() === ReservationStatus::PENDING_PAYMENT) {
            $reservation->setStatus(ReservationStatus::PAYMENT_PROOF_RECEIVED);
            $this->em->flush();
        }
    }

    public function confirm(Reservation $reservation): void
    {
        $this->em->wrapInTransaction(function () use ($reservation): void {
            $reservation->setStatus(ReservationStatus::CONFIRMED);
            $reservation->setConfirmedAt(new \DateTimeImmutable());
        });
    }

    public function confirmPayment(Reservation $reservation): void
    {
        $this->em->wrapInTransaction(function () use ($reservation): void {
            $reservation->setPaymentStatus(PaymentStatus::VALIDATED);
            $reservation->setConfirmedAt(new \DateTimeImmutable());
        });
    }

    public function cancelPayment(Reservation $reservation): void
    {
        $this->em->wrapInTransaction(function () use ($reservation): void {
            $reservation->setPaymentStatus(PaymentStatus::CANCELED);
            $reservation->setConfirmedAt(new \DateTimeImmutable());
        });
    }

    public function cancel(Reservation $reservation): void
    {
        $reservation->setStatus(ReservationStatus::CANCELLED);
        $this->em->flush();
    }

    public function markUnderReview(Reservation $reservation): void
    {
        $reservation->setStatus(ReservationStatus::UNDER_REVIEW);
        $this->em->flush();
    }
}
