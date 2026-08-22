<?php

namespace App\Security\Voter;

use App\Entity\Pack;
use App\Entity\User;
use App\Repository\PackProgressRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Pack>
 */
class PackVoter extends Voter
{
    public const VIEW = 'view_pack';

    public function __construct(
        private readonly PackProgressRepository $packProgressRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Pack;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Le destinataire n'accède qu'aux packs qu'il a effectivement commencés.
        return null !== $this->packProgressRepository->findOneByUserAndPack($user, $subject);
    }
}
