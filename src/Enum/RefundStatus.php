<?php

declare(strict_types=1);

namespace App\Enum;

enum RefundStatus: string
{
    case RECEIVED   = 'RECEIVED';
    case PROCESSING = 'PROCESSING';
    case REFUNDED   = 'REFUNDED';
    case REJECTED   = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED   => 'Reçue',
            self::PROCESSING => 'En cours de traitement',
            self::REFUNDED   => 'Remboursée',
            self::REJECTED   => 'Refusée',
        };
    }
}
