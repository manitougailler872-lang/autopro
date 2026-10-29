<?php
declare(strict_types=1);
namespace App\Enum;

enum CategoryTypeEnum: string
{
    case CITADINE = 'Citadine';
    case BERLINE = 'Berline';
    case SUV = 'SUV';
    case FORXFOR = '4x4';
    case UTILITAIRE = 'Utilitaire';
    case BREAK = 'Break';
    case CAMPING_CAR = 'Camping-Car';
    case COUPE = 'Coupé';
    case FOURGON = 'Fourgon';
    case PICKUP = 'Pick-Up';
    case SPORT = 'Sport';
    case VAN = 'Van';

    public function label(): string
    {
        return match ($this) {
            self::CITADINE => 'Citadine',
            self::BERLINE => 'Berline',
            self::SUV => 'SUV',
            self::FORXFOR => '4x4',
            self::UTILITAIRE => 'Utilitaire',
            self::BREAK => 'Break',
            self::CAMPING_CAR => 'Camping-Car',
            self::COUPE => 'Coupé',
            self::FOURGON => 'Fourgon',
            self::PICKUP => 'Pick-Up',
            self::SPORT => 'Sport',
            self::VAN => 'Van',
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