<?php

declare(strict_types=1);

namespace App\Enum;

enum ReservationStatus: string
{
    case PENDING_PAYMENT         = 'PENDING_PAYMENT';
    case PAYMENT_PROOF_RECEIVED  = 'PAYMENT_PROOF_RECEIVED';
    case UNDER_REVIEW            = 'UNDER_REVIEW';
    case CONFIRMED               = 'CONFIRMED';
    case CANCELLED               = 'CANCELLED';
    case REFUNDED                = 'REFUNDED';
    case FUNDS_RELEASED = 'FUNDS_RELEASED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT        => 'En attente de paiement',
            self::PAYMENT_PROOF_RECEIVED => 'Preuve de virement reçue',
            self::UNDER_REVIEW           => 'En cours de vérification',
            self::CONFIRMED              => 'Confirmée',
            self::CANCELLED              => 'Annulée',
            self::REFUNDED               => 'Remboursée',
            self::FUNDS_RELEASED         => 'Fonds libérés',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT, self::PAYMENT_PROOF_RECEIVED, self::UNDER_REVIEW => 'badge-pending',
            self::CONFIRMED, self::FUNDS_RELEASED => 'badge-success',
            self::CANCELLED  => 'badge-danger',
            self::REFUNDED   => 'badge-neutral',
        };
    }

    /** True when the customer is expected to act (release funds or ask refund) */
    public function isAwaitingCustomerRelease(): bool
    {
        return $this === self::CONFIRMED || $this === self::PENDING_PAYMENT;
    }
}
