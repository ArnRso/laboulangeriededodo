<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\Avatar;
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
        $existing = $this->userRepository->findOneByEmail($email);

        if (null === $existing) {
            $user = $this->invitationService->invite($email, [User::ROLE_RECIPIENT]);
            $user->setDisplayName($displayName)->setAvatar($avatar);
            $this->entityManager->flush();

            $token = $user->getInvitationToken();

            if (null === $token) {
                throw new \LogicException('Le token d\'invitation n\'a pas pu être généré.');
            }

            $this->invitationMailer->sendRecipientInvitation($user, $token);

            return $user;
        }

        if ($existing->isAdmin()) {
            throw new \LogicException('Cette adresse est déjà celle d\'un administrateur.');
        }

        if (null !== $existing->getPassword()) {
            throw new \LogicException(sprintf('%s a déjà activé son compte.', $existing->getPublicName()));
        }

        // Un renvoi permet aussi de corriger le prénom ou l'avatar.
        $existing->setDisplayName($displayName)->setAvatar($avatar);

        $token = $this->invitationService->refreshInvitationToken($existing);
        $this->entityManager->flush();

        $this->invitationMailer->sendRecipientInvitation($existing, $token);

        return $existing;
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
