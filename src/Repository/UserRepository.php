<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Le rôle est stocké en JSON : la recherche porte sur la chaîne sérialisée,
     * d'où le passage par du SQL natif que DQL ne sait pas exprimer.
     */
    public function findOneByRole(string $role): ?User
    {
        $id = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT id FROM "user" WHERE roles::text LIKE :role ORDER BY id ASC LIMIT 1',
            ['role' => '%"'.$role.'"%'],
        );

        return false === $id ? null : $this->find($id);
    }

    public function findOneByValidInvitationToken(string $token): ?User
    {
        $results = $this->createQueryBuilder('u')
            ->andWhere('u.invitationToken = :token')
            ->andWhere('u.invitationExpiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $results[0] ?? null;
    }
}
