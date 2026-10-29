<?php

declare(strict_types=1);

namespace App\Enum;

enum PaymentStatus: string
{
    case PENDING   = 'PENDING';
    case VALIDATED = 'VALIDATED';
    case CANCELED   = 'CANCELED';
    case REJECTED   = 'REJECTED';
    case PROCESSING = 'PROCESSING';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'En attente',
            self::VALIDATED => 'Validée',
            self::CANCELED   => 'Annulé',
            self::REJECTED   => 'Refusée',
            self::PROCESSING => 'Processing'
        };
    }
}
