<?php

namespace App\Service;

use App\Entity\FeedSkip;
use App\Entity\Media;
use App\Entity\MediaAccess;
use App\Entity\User;
use App\Repository\FeedSkipRepository;
use App\Repository\MediaAccessRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Ce que le destinataire fait du fil : ouvrir une notification, et lire
 * l'état du fil sous une forme prête à afficher.
 */
class FeedService
{
    public function __construct(
        private readonly FeedUnlockService $unlockService,
        private readonly MediaAccessRepository $mediaAccessRepository,
        private readonly FeedSkipRepository $feedSkipRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Enregistre l'ouverture, ce qui fait partir le chrono de la suivante.
     * Rouvrir une notification déjà consultée ne change rien.
     *
     * @throws \LogicException si la notification n'est pas encore arrivée
     */
    public function open(User $user, Media $media): MediaAccess
    {
        $existing = $this->mediaAccessRepository->findOneByUserAndMedia($user, $media);

        if (null !== $existing) {
            return $existing;
        }

        if (!$this->unlockService->canOpen($user, $media)) {
            throw new \LogicException('Cette notification n\'est pas encore arrivée.');
        }

        $access = new MediaAccess();
        $access->setUser($user)
            ->setMedia($media)
            ->setOpenedAt($this->clock->now());

        $this->entityManager->persist($access);
        $this->entityManager->flush();

        return $access;
    }

    /**
     * Saute l'attente de la prochaine notification : elle devient disponible
     * immédiatement. Sert aux démonstrations.
     *
     * @return Media la notification débloquée
     *
     * @throws \LogicException si aucune notification n'est en attente
     */
    public function skipWait(User $user): Media
    {
        $next = $this->getOverview($user)->next;

        if (null === $next) {
            throw new \LogicException('Aucune notification n\'attend son tour.');
        }

        return $this->skipWaitFor($user, $next->media);
    }

    /**
     * Débloque une notification précise. Deux appuis rapprochés visent la même
     * notification : le second ne doit pas buter sur la contrainte d'unicité.
     */
    public function skipWaitFor(User $user, Media $media): Media
    {
        $existing = $this->feedSkipRepository->findOneBy(['user' => $user, 'media' => $media]);

        if (null !== $existing) {
            return $media;
        }

        $skip = new FeedSkip();
        $skip->setUser($user)
            ->setMedia($media)
            ->setSkippedAt($this->clock->now());

        $this->entityManager->persist($skip);
        $this->entityManager->flush();

        return $media;
    }

    public function hasOpened(User $user, Media $media): bool
    {
        return null !== $this->mediaAccessRepository->findOneByUserAndMedia($user, $media);
    }

    public function getOverview(User $user): FeedOverview
    {
        $fresh = null;
        $next = null;
        $seen = [];

        foreach ($this->unlockService->getFeedState($user) as $state) {
            if ($state->opened) {
                $seen[] = $state;
            } elseif ($state->unlockable) {
                $fresh = $state;
            } elseif (null === $next) {
                $next = $state;
            }
        }

        // Les consultées se lisent de la plus récente à la plus ancienne.
        usort($seen, static fn (NotificationState $a, NotificationState $b): int => $b->openedAt <=> $a->openedAt);

        return new FeedOverview($fresh, $next, $seen);
    }
}
