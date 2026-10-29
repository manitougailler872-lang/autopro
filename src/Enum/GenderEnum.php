<?php

declare(strict_types=1);

namespace App\Enum;

enum GenderEnum: string
{
    case MR  = 'MR';
    case MME = 'MLLE';

    public function label(): string
    {
        return match ($this) {
            self::MR  => 'Monsieur',
            self::MME => 'Madame'
        };
    }
}
