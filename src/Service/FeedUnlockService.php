<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaAccessRepository;
use App\Repository\MediaRepository;
use Psr\Clock\ClockInterface;

/**
 * Détermine quelles notifications du fil sont accessibles au destinataire.
 *
 * Règles :
 *  - les notifications arrivent dans l'ordre de leur position ;
 *  - la première est disponible immédiatement ;
 *  - chaque suivante arrive `delayMinutes` après l'ouverture de la précédente ;
 *  - une notification ouverte le reste définitivement ;
 *  - les brouillons non publiés n'existent pas pour le destinataire.
 */
class FeedUnlockService
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaAccessRepository $mediaAccessRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return list<NotificationState> dans l'ordre du fil
     */
    public function getFeedState(User $user): array
    {
        $openedAt = [];

        foreach ($this->mediaAccessRepository->findForUser($user) as $access) {
            $openedAt[spl_object_id($access->getMedia())] = $access->getOpenedAt();
        }

        $states = [];
        $previousOpenedAt = null;
        $currentFound = false;

        foreach ($this->mediaRepository->findPublishedOrdered() as $media) {
            $id = spl_object_id($media);

            if (isset($openedAt[$id])) {
                $states[] = NotificationState::opened($media, $openedAt[$id]);
                $previousOpenedAt = $openedAt[$id];

                continue;
            }

            // Seule la première non ouverte est candidate : les suivantes
            // attendent, sans date tant que celle-ci n'est pas consultée.
            if ($currentFound) {
                $states[] = NotificationState::locked($media, null);

                continue;
            }

            $currentFound = true;

            if (null === $previousOpenedAt) {
                $states[] = NotificationState::unlockable($media);

                continue;
            }

            $availableAt = $previousOpenedAt->add(new \DateInterval(sprintf('PT%dM', $media->getDelayMinutes())));

            $states[] = $availableAt <= $this->clock->now()
                ? NotificationState::unlockable($media)
                : NotificationState::locked($media, $availableAt);
        }

        return $states;
    }

    public function getStateFor(User $user, Media $media): ?NotificationState
    {
        foreach ($this->getFeedState($user) as $state) {
            if ($state->media === $media) {
                return $state;
            }
        }

        return null;
    }

    public function canOpen(User $user, Media $media): bool
    {
        return $this->getStateFor($user, $media)?->isAccessible() ?? false;
    }
}
