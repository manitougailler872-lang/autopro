<?php

declare(strict_types=1);

namespace App\Enum;

enum ReviewStatus: string
{
    case PENDING   = 'PENDING';
    case PUBLISHED = 'PUBLISHED';
    case HIDDEN    = 'HIDDEN';
    case REJECTED  = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'En attente',
            self::PUBLISHED => 'Publié',
            self::HIDDEN    => 'Masqué',
            self::REJECTED  => 'Refusé',
        };
    }
}
