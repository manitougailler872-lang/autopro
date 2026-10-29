<?php

declare(strict_types=1);

namespace App\Enum;

enum MailType: string
{
    case CONTRAT_DE_VENTE   = 'CONTRAT_DE_VENTE';
    case INFORMATION   = 'INFORMATION';


    public function label(): string
    {
        return match ($this) {
            self::CONTRAT_DE_VENTE   => 'Contrat de vente',
            self::INFORMATION   => 'Information complémentaire',
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
