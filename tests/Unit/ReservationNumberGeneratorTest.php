<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\ReservationNumberGenerator;
use PHPUnit\Framework\TestCase;

final class ReservationNumberGeneratorTest extends TestCase
{
    public function testFirstNumberOfTheYear(): void
    {
        $repo = $this->createMock(ReservationRepository::class);
        $repo->method('findLatestForYear')->willReturn(null);

        $gen = new ReservationNumberGenerator($repo);
        $number = $gen->generate(new \DateTimeImmutable('2026-05-01'));

        $this->assertSame('LC-2026-000001', $number);
    }

    public function testIncrementsFromLatest(): void
    {
        $latest = new Reservation();
        $latest->setReservationNumber('LC-2026-000042');

        $repo = $this->createMock(ReservationRepository::class);
        $repo->method('findLatestForYear')->willReturn($latest);

        $gen = new ReservationNumberGenerator($repo);
        $this->assertSame('LC-2026-000043', $gen->generate(new \DateTimeImmutable('2026-06-01')));
    }

    public function testFormatMatchesRegex(): void
    {
        $repo = $this->createMock(ReservationRepository::class);
        $repo->method('findLatestForYear')->willReturn(null);
        $gen = new ReservationNumberGenerator($repo);

        $this->assertMatchesRegularExpression('/^LC-\d{4}-\d{6}$/', $gen->generate());
    }
}
