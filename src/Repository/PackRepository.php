<?php

namespace App\Repository;

use App\Entity\Pack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pack>
 */
class PackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pack::class);
    }

    /**
     * @return list<Pack>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Pack>
     */
    public function findPublishedOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.published = true')
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMaxPosition(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COALESCE(MAX(p.position), -1)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
