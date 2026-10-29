<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Enum\FuelType;
use App\Enum\ReceptionMode;
use App\Enum\ReservationStatus;
use PHPUnit\Framework\TestCase;

final class EnumsTest extends TestCase
{
    public function testReservationStatusLabelsAreFrench(): void
    {
        $this->assertSame('En attente de paiement',      ReservationStatus::PENDING_PAYMENT->label());
        $this->assertSame('Confirmée',                   ReservationStatus::CONFIRMED->label());
        $this->assertSame('Annulée',                     ReservationStatus::CANCELLED->label());
    }

    public function testReceptionModeLabels(): void
    {
        $this->assertSame('Retrait au garage',    ReceptionMode::PICKUP->label());
        $this->assertSame('Livraison à domicile', ReceptionMode::DELIVERY->label());
    }

    public function testFuelTypeChoicesReturnLabelAsKey(): void
    {
        $choices = FuelType::choices();
        $this->assertArrayHasKey('Essence',    $choices);
        $this->assertArrayHasKey('Diesel',     $choices);
        $this->assertSame('PETROL', $choices['Essence']);
    }
}
