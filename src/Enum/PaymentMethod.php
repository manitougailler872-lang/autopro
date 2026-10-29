<?php

declare(strict_types=1);

namespace App\Enum;

enum PaymentMethod: string
{
    case BANK_TRANSFER = 'BANK_TRANSFER';

    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Virement bancaire',
        };
    }
}
