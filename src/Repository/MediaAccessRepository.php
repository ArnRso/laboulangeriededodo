<?php

namespace App\Repository;

use App\Entity\Media;
use App\Entity\MediaAccess;
use App\Entity\Pack;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaAccess>
 */
class MediaAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaAccess::class);
    }

    public function findOneByUserAndMedia(User $user, Media $media): ?MediaAccess
    {
        return $this->findOneBy(['user' => $user, 'media' => $media]);
    }

    public function countForUserAndPack(User $user, Pack $pack): int
    {
        return (int) $this->createQueryBuilder('ma')
            ->select('COUNT(ma.id)')
            ->join('ma.media', 'm')
            ->andWhere('ma.user = :user')
            ->andWhere('m.pack = :pack')
            ->setParameter('user', $user)
            ->setParameter('pack', $pack)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return MediaAccess[]
     */
    public function findForUserAndPack(User $user, Pack $pack): array
    {
        return $this->createQueryBuilder('ma')
            ->join('ma.media', 'm')
            ->andWhere('ma.user = :user')
            ->andWhere('m.pack = :pack')
            ->setParameter('user', $user)
            ->setParameter('pack', $pack)
            ->orderBy('m.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
