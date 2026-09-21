<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\Avatar;
use App\Enum\InvitationLifetime;
use App\Repository\FeedSkipRepository;
use App\Repository\MediaAccessRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Les destinataires du cadeau : les inviter, leur renvoyer un lien, et les
 * remettre au début du fil. Ils partagent le même fil mais chacun le
 * parcourt à son rythme.
 */
readonly class RecipientInviter
{
    public function __construct(
        private UserRepository $userRepository,
        private InvitationService $invitationService,
        private InvitationMailer $invitationMailer,
        private MediaAccessRepository $mediaAccessRepository,
        private FeedSkipRepository $feedSkipRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<User>
     */
    public function findRecipients(): array
    {
        return $this->userRepository->findByRole(User::ROLE_RECIPIENT);
    }

    /**
     * Invite une personne de plus, ou renvoie son lien si elle n'a pas encore
     * activé son compte.
     *
     * @throws \DateMalformedIntervalStringException
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    public function invite(string $email, string $displayName, Avatar $avatar): User
    {
        [$user, $token] = $this->prepare($email, $displayName, $avatar);

        $this->invitationMailer->sendRecipientInvitation($user, $token);

        return $user;
    }

    /**
     * Prépare l'invitation sans rien envoyer : à l'administrateur de
     * transmettre le lien par le moyen qu'il veut.
     *
     * @return array{User, string} la personne invitée et son jeton
     *
     * @throws \DateMalformedIntervalStringException
     * @throws RandomException
     */
    public function inviteWithoutEmail(string $email, string $displayName, Avatar $avatar, InvitationLifetime $lifetime): array
    {
        return $this->prepare($email, $displayName, $avatar, $lifetime);
    }

    /**
     * Crée le compte ou rafraîchit son jeton, sans décider de l'envoi.
     *
     * @return array{User, string}
     *
     * @throws \DateMalformedIntervalStringException
     * @throws RandomException
     */
    private function prepare(string $email, string $displayName, Avatar $avatar, ?InvitationLifetime $lifetime = null): array
    {
        $existing = $this->userRepository->findOneByEmail($email);

        if (null === $existing) {
            $user = $this->invitationService->invite($email, [User::ROLE_RECIPIENT], $lifetime);
            $user->setDisplayName($displayName)->setAvatar($avatar);
            $this->entityManager->flush();

            $token = $user->getInvitationToken();

            if (null === $token) {
                throw new \LogicException('Le token d\'invitation n\'a pas pu être généré.');
            }

            return [$user, $token];
        }

        if ($existing->isAdmin()) {
            throw new \LogicException('Cette adresse est déjà celle d\'un administrateur.');
        }

        if (null !== $existing->getPassword()) {
            throw new \LogicException(sprintf('%s a déjà activé son compte.', $existing->getPublicName()));
        }

        // Un renvoi permet aussi de corriger le prénom ou l'avatar.
        $existing->setDisplayName($displayName)->setAvatar($avatar);

        $token = $this->invitationService->refreshInvitationToken($existing, $lifetime);
        $this->entityManager->flush();

        return [$existing, $token];
    }

    /**
     * Renvoie le mail d'invitation à quelqu'un qui n'a pas encore activé son
     * compte, avec un jeton neuf.
     *
     * @throws \DateMalformedIntervalStringException
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    public function resendEmail(User $recipient): void
    {
        $token = $this->refreshFor($recipient);

        $this->invitationMailer->sendRecipientInvitation($recipient, $token);
    }

    /**
     * Refabrique le lien de quelqu'un qui n'a pas encore activé son compte,
     * sans rien envoyer.
     *
     * @throws \DateMalformedIntervalStringException
     * @throws RandomException
     */
    public function refreshLink(User $recipient, InvitationLifetime $lifetime): string
    {
        return $this->refreshFor($recipient, $lifetime);
    }

    /**
     * Retire quelqu'un du cadeau : son compte, ses ouvertures et ses coups
     * de pouce s'en vont avec lui.
     */
    public function delete(User $recipient): void
    {
        if ($recipient->isAdmin()) {
            throw new \LogicException('Un administrateur ne se supprime pas depuis cette page.');
        }

        $this->resetProgress($recipient);

        $this->entityManager->remove($recipient);
        $this->entityManager->flush();
    }

    /**
     * Un jeton neuf pour quelqu'un dont le compte attend encore son mot de
     * passe.
     *
     * @throws \DateMalformedIntervalStringException
     * @throws RandomException
     */
    private function refreshFor(User $recipient, ?InvitationLifetime $lifetime = null): string
    {
        if (null !== $recipient->getPassword()) {
            throw new \LogicException(sprintf('%s a déjà activé son compte.', $recipient->getPublicName()));
        }

        $token = $this->invitationService->refreshInvitationToken($recipient, $lifetime);
        $this->entityManager->flush();

        return $token;
    }

    /**
     * Remet une personne au début du fil : ses ouvertures et ses coups de
     * pouce disparaissent, son compte reste.
     */
    public function resetProgress(User $recipient): void
    {
        foreach ($this->mediaAccessRepository->findForUser($recipient) as $access) {
            $this->entityManager->remove($access);
        }

        foreach ($this->feedSkipRepository->findForUser($recipient) as $skip) {
            $this->entityManager->remove($skip);
        }

        $this->entityManager->flush();
    }
}
