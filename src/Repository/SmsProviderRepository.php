<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\SmsProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SmsProvider> */
class SmsProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, SmsProvider::class); }
}
