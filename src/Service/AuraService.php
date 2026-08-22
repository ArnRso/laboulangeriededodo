<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\MediaAccessRepository;
use Psr\Clock\ClockInterface;

/**
 * L'aura est la somme des points des notifications ouvertes. Elle peut
 * baisser : certaines ouvertures coûtent cher.
 */
class AuraService
{
    public function __construct(
        private readonly MediaAccessRepository $mediaAccessRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function total(User $user): int
    {
        return $this->mediaAccessRepository->sumAuraForUser($user);
    }

    public function today(User $user): int
    {
        return $this->mediaAccessRepository->sumAuraForUser($user, $this->clock->now()->setTime(0, 0));
    }
}
