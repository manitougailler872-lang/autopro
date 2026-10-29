<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findOneByUuid(string $uuid): ?Reservation
    {
        /*try {
            $uuidObj = $uuid;
        } catch (\Throwable) {
            return null;
        }*/
        return $this->createQueryBuilder('r')
            ->andWhere('r.uuid = :u')->setParameter('u', $uuid, 'uuid')
            ->getQuery()->getOneOrNullResult();
    }

    public function countByStatus(ReservationStatus $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :s')->setParameter('s', $status)
            ->getQuery()->getSingleScalarResult();
    }

    public function findLatestForYear(int $year): ?Reservation
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year));
        $end   = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year + 1));
        return $this->createQueryBuilder('r')
            ->andWhere('r.createdAt >= :s')->setParameter('s', $start)
            ->andWhere('r.createdAt < :e')->setParameter('e', $end)
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
