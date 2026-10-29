<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FreinageSystem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FreinageSystem>
 */
class FreinageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FreinageSystem::class);
    }
}
