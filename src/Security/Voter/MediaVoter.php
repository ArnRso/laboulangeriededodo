<?php

namespace App\Security\Voter;

use App\Entity\Media;
use App\Entity\User;
use App\Repository\PackProgressRepository;
use App\Service\UnlockService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Media>
 */
class MediaVoter extends Voter
{
    public const VIEW = 'view';

    public function __construct(
        private readonly PackProgressRepository $packProgressRepository,
        private readonly UnlockService $unlockService,
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

        // L'administration prépare les packs : elle voit tout.
        if ($user->isAdmin()) {
            return true;
        }

        $progress = $this->packProgressRepository->findOneByUserAndPack($user, $subject->getPack());

        if (null === $progress) {
            return false;
        }

        return $this->unlockService->canOpen($user, $progress, $subject);
    }
}
