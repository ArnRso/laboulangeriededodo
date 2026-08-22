<?php

namespace App\Repository;

use App\Entity\Media;
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
     * Le fil complet, brouillons compris, dans l'ordre de lecture.
     *
     * @return list<Media>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.position', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Le fil tel que le destinataire le parcourt.
     *
     * @return list<Media>
     */
    public function findPublishedOrdered(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.published = true')
            ->orderBy('m.position', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMaxPosition(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COALESCE(MAX(m.position), -1)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
