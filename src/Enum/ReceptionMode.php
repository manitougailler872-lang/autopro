<?php

declare(strict_types=1);

namespace App\Enum;

enum ReceptionMode: string
{
    case PICKUP   = 'PICKUP';
    case DELIVERY = 'DELIVERY';

    public function label(): string
    {
        return match ($this) {
            self::PICKUP   => 'Retrait au garage',
            self::DELIVERY => 'Livraison à domicile',
        };
    }
}
