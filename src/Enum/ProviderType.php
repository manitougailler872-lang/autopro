<?php

declare(strict_types=1);

namespace App\Enum;

enum ProviderType: string
{
    case BREVO   = 'BREVO';
    case OVH   = 'OVH';
    case TWILO   = 'TWILO';

    public function label(): string
    {
        return match ($this) {
            self::BREVO   => 'BREVO',
            self::OVH   => 'OVH',
            self::TWILO   => 'TWILO',
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
