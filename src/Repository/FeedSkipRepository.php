<?php

namespace App\Repository;

use App\Entity\FeedSkip;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeedSkip>
 */
class FeedSkipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeedSkip::class);
    }

    /**
     * @return list<FeedSkip>
     */
    public function findForUser(User $user): array
    {
        // Le média est hydraté avec le saut : sans cela, Doctrine renverrait un
        // proxy distinct de l'instance du fil et la comparaison échouerait.
        return $this->createQueryBuilder('s')
            ->join('s.media', 'm')
            ->addSelect('m')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<FeedSkip>
     */
    public function findByMedia(Media $media): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.media = :media')
            ->setParameter('media', $media)
            ->getQuery()
            ->getResult();
    }
}
