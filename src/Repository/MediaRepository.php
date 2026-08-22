<?php

namespace App\Repository;

use App\Entity\Media;
use App\Entity\Pack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * @return list<Media>
     */
    public function findByPackOrdered(Pack $pack): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.pack = :pack')
            ->setParameter('pack', $pack)
            ->orderBy('m.position', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMaxPosition(Pack $pack): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COALESCE(MAX(m.position), -1)')
            ->andWhere('m.pack = :pack')
            ->setParameter('pack', $pack)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
