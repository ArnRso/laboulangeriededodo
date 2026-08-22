<?php

namespace App\Security\Voter;

use App\Entity\Media;
use App\Entity\User;
use App\Service\FeedUnlockService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Media>
 */
class MediaVoter extends Voter
{
    public const string VIEW = 'view';

    public function __construct(
        private readonly FeedUnlockService $unlockService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Media;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // L'administration prépare le fil : elle voit tout, brouillons compris.
        if ($user->isAdmin()) {
            return true;
        }

        return $this->unlockService->canOpen($user, $subject);
    }
}
