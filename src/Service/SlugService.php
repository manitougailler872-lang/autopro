<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\VehicleRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugService
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly VehicleRepository $vehicles,
    ) {}

    public function makeUniqueVehicleSlug(string $source, ?int $excludeId = null): string
    {
        $base = strtolower((string) $this->slugger->slug($source));
        $base = trim(preg_replace('/-+/', '-', $base) ?? $base, '-');
        if ($base === '') {
            $base = 'vehicule';
        }

        $candidate = $base;
        $i = 2;
        while ($this->vehicles->slugExists($candidate, $excludeId)) {
            $candidate = $base.'-'.$i;
            $i++;
            if ($i > 500) { // safety net
                $candidate = $base.'-'.bin2hex(random_bytes(3));
                break;
            }
        }
        return $candidate;
    }
}
