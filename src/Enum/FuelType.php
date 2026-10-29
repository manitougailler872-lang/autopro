<?php

declare(strict_types=1);

namespace App\Enum;

enum FuelType: string
{
    case PETROL   = 'PETROL';
    case DIESEL   = 'DIESEL';
    case HYBRID   = 'HYBRID';
    case ELECTRIC = 'ELECTRIC';
    case LPG      = 'LPG';

    public function label(): string
    {
        return match ($this) {
            self::PETROL   => 'Essence',
            self::DIESEL   => 'Diesel',
            self::HYBRID   => 'Hybride',
            self::ELECTRIC => 'Électrique',
            self::LPG      => 'GPL',
        };
    }

    /** @return array<string,string> */
    public static function choices(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->label()] = $case->value;
        }
        return $out;
    }
}
