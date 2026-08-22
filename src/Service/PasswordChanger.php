<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Changement de mot de passe par un utilisateur déjà connecté.
 */
readonly class PasswordChanger
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function isCurrentPasswordValid(User $user, string $plainPassword): bool
    {
        // Un compte encore en attente d'invitation n'a pas de mot de passe à confronter.
        if (null === $user->getPassword()) {
            return false;
        }

        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    /**
     * @throws \LogicException si l'ancien mot de passe ne correspond pas
     */
    public function change(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->isCurrentPasswordValid($user, $currentPassword)) {
            throw new \LogicException('Le mot de passe actuel est incorrect.');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));

        $this->entityManager->flush();
    }
}
