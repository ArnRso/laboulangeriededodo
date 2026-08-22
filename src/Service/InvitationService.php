<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Random\RandomException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Gère le cycle de vie des invitations : création d'un compte sans mot de passe,
 * puis définition de celui-ci via un lien à usage unique et à durée limitée.
 */
class InvitationService
{
    public const int TOKEN_LIFETIME_DAYS = 7;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Crée un utilisateur invité, sans mot de passe, muni d'un token d'invitation.
     *
     * @param list<string> $roles
     *
     * @throws \InvalidArgumentException si un compte existe déjà pour cet email
     * @throws RandomException
     * @throws \DateMalformedIntervalStringException
     */
    public function invite(string $email, array $roles): User
    {
        if (null !== $this->userRepository->findOneByEmail($email)) {
            throw new \InvalidArgumentException(sprintf('Un compte existe déjà pour "%s".', $email));
        }

        $user = new User();
        $user->setEmail($email)->setRoles($roles);

        $this->refreshInvitationToken($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Régénère le token d'un utilisateur, par exemple pour renvoyer une invitation.
     *
     * @throws RandomException
     * @throws \DateMalformedIntervalStringException
     */
    public function refreshInvitationToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));

        $user->setInvitationToken($token)
            ->setInvitationExpiresAt(
                $this->clock->now()->add(new \DateInterval(sprintf('P%dD', self::TOKEN_LIFETIME_DAYS)))
            );

        return $token;
    }

    /**
     * Retourne l'utilisateur associé à un token encore valide, ou null.
     */
    public function findUserByToken(string $token): ?User
    {
        $candidate = $this->userRepository->findOneBy(['invitationToken' => $token]);

        if (null === $candidate) {
            return null;
        }

        $expiresAt = $candidate->getInvitationExpiresAt();

        if (null === $expiresAt || $expiresAt <= $this->clock->now()) {
            return null;
        }

        return $candidate;
    }

    /**
     * Définit le mot de passe et consomme le token : le lien ne peut plus resservir.
     */
    public function completeInvitation(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword))
            ->setInvitationToken(null)
            ->setInvitationExpiresAt(null);

        $this->entityManager->flush();
    }

    public function hasPendingInvitation(User $user): bool
    {
        $expiresAt = $user->getInvitationExpiresAt();

        return null !== $user->getInvitationToken()
            && null !== $expiresAt
            && $expiresAt > $this->clock->now();
    }
}
