<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Review;
use App\Enum\ReviewStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /** @return Review[] */
    public function findPublished(int $limit = 6): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :s')->setParameter('s', ReviewStatus::PUBLISHED)
            ->orderBy('r.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    public function countByStatus(ReviewStatus $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :s')->setParameter('s', $status)
            ->getQuery()->getSingleScalarResult();
    }
}
