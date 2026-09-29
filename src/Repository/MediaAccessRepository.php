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
     * Les ouvertures de tout le fil, groupées par notification : de quoi
     * montrer qui a lu quoi sans une requête par ligne.
     *
     * @return array<int, list<MediaAccess>>
     */
    public function findAllGroupedByMedia(): array
    {
        $accesses = $this->createQueryBuilder('ma')
            ->join('ma.user', 'u')
            ->addSelect('u')
            ->join('ma.media', 'm')
            ->orderBy('ma.openedAt', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];

        foreach ($accesses as $access) {
            $grouped[(int) $access->getMedia()->getId()][] = $access;
        }

        return $grouped;
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
