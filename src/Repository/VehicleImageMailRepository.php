<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\VehiculeImagesMail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehiculeImagesMail>
 */
class VehicleImageMailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehiculeImagesMail::class);
    }
}
