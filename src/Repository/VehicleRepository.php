<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicle>
 */
class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    /** @return Vehicle[] */
    public function findPublished(int $limit = null, ?string $vehicleType = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.published = true  and v.isAvailable = true')
            ->orderBy('v.createdAt', 'DESC');
        if(!empty($vehicleType)){
             $qb->andWhere('v.vehicleType = :vehicleType')->setParameter('vehicleType',$vehicleType);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }

     /** @return Vehicle[] */
    public function findPublishedAuction(int $limit = null, ?string $vehicleType = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.published = true  and v.isAvailable = true and  v.isAuction = true')
            ->orderBy('v.createdAt', 'DESC');
        if(!empty($vehicleType)){
             $qb->andWhere('v.vehicleType = :vehicleType')->setParameter('vehicleType',$vehicleType);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }

    /** @return Vehicle[] */
    public function findReserved(int $limit = null, ?string $vehicleType = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.published = true and v.reserved = true')
            ->orderBy('v.createdAt', 'DESC');
        if(!empty($vehicleType)){
             $qb->andWhere('v.vehicleType = :vehicleType')->setParameter('vehicleType',$vehicleType);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }

    public  function findGoodDeals(int $limit = null,?string $vehicleType = null): array
    {
       
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.bestDeal = true')
            ->andWhere('v.reserved = false or v.reserved is null')
            ->andWhere('v.published = true')
            ->andWhere('v.isAuction = false')
            ->orderBy('v.createdAt', 'DESC');
        if(!empty($vehicleType)){
             $qb->andWhere('v.vehicleType = :vehicleType')->setParameter('vehicleType',$vehicleType);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->getResult();
    }

    public function findOnePublishedBySlug(string $slug): ?Vehicle
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.slug = :slug')
            ->andWhere('v.published = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.slug = :slug')
            ->setParameter('slug', $slug);
        if ($excludeId !== null) {
            $qb->andWhere('v.id <> :id')->setParameter('id', $excludeId);
        }
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
