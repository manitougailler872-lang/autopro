<?php

declare(strict_types=1);

namespace App\Enum;

enum SmsStatus: string
{
    case PROCESSING = 'PROCESSING';
    case DELIVERY   = 'DELIVERY';
    case REJECTED   = 'REJECTED';
    case CANCELED   = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::PROCESSING => 'En cours de traitement',
            self::DELIVERY   => 'Délivré',
            self::REJECTED   => 'Rejeté',
            self::CANCELED   => 'Annulé',
        };
    }
}
