<?php
declare(strict_types=1);
namespace App\Enum;

enum VehicleTypeEnum: string
{
    case CAR = 'CAR';
    case PIECE = 'PIECE';

    public function label(): string
    {
        return match ($this) {
            self::CAR => 'Voiture',
            self::PIECE => 'Pièce',
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