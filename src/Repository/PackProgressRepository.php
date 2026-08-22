<?php

namespace App\Repository;

use App\Entity\Pack;
use App\Entity\PackProgress;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PackProgress>
 */
class PackProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PackProgress::class);
    }

    public function findOneByUserAndPack(User $user, Pack $pack): ?PackProgress
    {
        return $this->findOneBy(['user' => $user, 'pack' => $pack]);
    }

    public function findActiveForUser(User $user): ?PackProgress
    {
        $results = $this->createQueryBuilder('pp')
            ->andWhere('pp.user = :user')
            ->andWhere('pp.completedAt IS NULL')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $results[0] ?? null;
    }

    /**
     * @return list<PackProgress>
     */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('pp')
            ->andWhere('pp.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pp.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
