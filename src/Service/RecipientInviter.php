<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Invite le destinataire du cadeau, ou lui renvoie son lien.
 */
class RecipientInviter
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly InvitationService $invitationService,
        private readonly InvitationMailer $invitationMailer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findRecipient(): ?User
    {
        return $this->userRepository->findOneByRole(User::ROLE_RECIPIENT);
    }

    /**
     * Invite le destinataire s'il n'existe pas encore, sinon lui renvoie un lien neuf.
     *
     * @throws \LogicException si le compte a déjà été activé
     */
    public function invite(string $email): User
    {
        $existing = $this->findRecipient();

        if (null === $existing) {
            $user = $this->invitationService->invite($email, [User::ROLE_RECIPIENT]);
            $token = $user->getInvitationToken();

            if (null === $token) {
                throw new \LogicException('Le token d\'invitation n\'a pas pu être généré.');
            }

            $this->invitationMailer->sendRecipientInvitation($user, $token);

            return $user;
        }

        if (null !== $existing->getPassword()) {
            throw new \LogicException('Le destinataire a déjà activé son compte.');
        }

        $token = $this->invitationService->refreshInvitationToken($existing);
        $this->entityManager->flush();

        $this->invitationMailer->sendRecipientInvitation($existing, $token);

        return $existing;
    }
}
