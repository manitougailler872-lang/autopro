<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ReservationRepository;

class ReservationNumberGenerator
{
    public function __construct(private readonly ReservationRepository $repo) {}

    public function generate(?\DateTimeImmutable $now = null): string
    {
        $now  = $now ?? new \DateTimeImmutable();
        $year = (int) $now->format('Y');

        $latest = $this->repo->findLatestForYear($year);
        $next   = 1;

        if ($latest !== null && preg_match('/^LC-\d{4}-(\d+)$/', $latest->getReservationNumber(), $m) === 1) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('LC-%d-%06d', $year, $next);
    }
}
