<?php

namespace App\Repository;

use App\Entity\Media;
use App\Entity\MediaAccess;
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

    /**
     * Ouvertures du destinataire, la plus récente en premier.
     *
     * @return list<MediaAccess>
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('ma')
            ->join('ma.media', 'm')
            ->addSelect('m')
            ->andWhere('ma.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ma.openedAt', 'DESC')
            ->addOrderBy('m.position', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Aura cumulée par les ouvertures, éventuellement à partir d'une date.
     */
    public function sumAuraForUser(User $user, ?\DateTimeImmutable $since = null): int
    {
        $builder = $this->createQueryBuilder('ma')
            ->select('COALESCE(SUM(m.auraPoints), 0)')
            ->join('ma.media', 'm')
            ->andWhere('ma.user = :user')
            ->setParameter('user', $user);

        if (null !== $since) {
            $builder->andWhere('ma.openedAt >= :since')->setParameter('since', $since);
        }

        return (int) $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<MediaAccess>
     */
    public function findByMedia(Media $media): array
    {
        return $this->createQueryBuilder('ma')
            ->andWhere('ma.media = :media')
            ->setParameter('media', $media)
            ->getQuery()
            ->getResult();
    }
}
