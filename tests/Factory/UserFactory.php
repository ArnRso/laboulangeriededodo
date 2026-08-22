<?php

namespace App\Tests\Factory;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Crée des utilisateurs pour les tests, sans dépendre d'un jeu de fixtures global.
 */
final readonly class UserFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function createAdmin(string $email = 'admin@example.com', string $password = 'admin-password'): User
    {
        return $this->create($email, [User::ROLE_ADMIN], $password);
    }

    public function createRecipient(string $email = 'dorian@example.com', string $password = 'dorian-password'): User
    {
        return $this->create($email, [User::ROLE_RECIPIENT], $password);
    }

    /**
     * Utilisateur invité mais qui n'a pas encore défini son mot de passe.
     */
    public function createInvited(
        string $email = 'invite@example.com',
        string $token = 'un-token-de-test',
        ?\DateTimeImmutable $expiresAt = null,
    ): User {
        $user = new User();
        $user->setEmail($email)
            ->setRoles([User::ROLE_ADMIN])
            ->setInvitationToken($token)
            ->setInvitationExpiresAt($expiresAt ?? new \DateTimeImmutable('+7 days'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param list<string> $roles
     */
    private function create(string $email, array $roles, string $password): User
    {
        $user = new User();
        $user->setEmail($email)->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
