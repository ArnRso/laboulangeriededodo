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
        // Les étiquettes viennent avec : sans cette jointure, le back-office
        // les redemande une fois par notification.
        return $this->createQueryBuilder('m')
            ->leftJoin('m.tags', 't')
            ->addSelect('t')
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
        // Un brouillon n'a pas d'écran : il n'existe pas pour le destinataire,
        // même si un jour la validation laissait passer un « published ».
        return $this->createQueryBuilder('m')
            ->andWhere('m.published = true')
            ->andWhere('m.appKind IS NOT NULL')
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
