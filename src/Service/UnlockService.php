<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\Pack;
use App\Entity\PackProgress;
use App\Entity\User;
use App\Repository\MediaAccessRepository;
use App\Repository\MediaRepository;
use Psr\Clock\ClockInterface;

/**
 * Détermine quels médias d'un pack sont accessibles à un utilisateur.
 *
 * Règles :
 *  - les médias sont ouverts dans l'ordre de leur position ;
 *  - le premier média est disponible dès le démarrage du pack ;
 *  - le suivant se débloque `unlockDelayHours` après la dernière ouverture ;
 *  - un média déjà ouvert le reste définitivement.
 */
class UnlockService
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaAccessRepository $mediaAccessRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return UnlockState[] indexés par position dans le pack
     */
    public function getPackState(User $user, PackProgress $progress): array
    {
        $pack = $progress->getPack();
        $medias = $this->mediaRepository->findByPackOrdered($pack);
        $openedIds = $this->getOpenedMediaIds($user, $pack);
        $availableAt = $this->getNextAvailabilityDate($progress);

        $states = [];
        $nextIsUnlockable = true;

        foreach ($medias as $media) {
            if (isset($openedIds[$media->getId()])) {
                $states[] = UnlockState::opened($media);

                continue;
            }

            if (!$nextIsUnlockable) {
                $states[] = UnlockState::locked($media, null);

                continue;
            }

            // Premier média non ouvert : seul candidat au déblocage.
            $nextIsUnlockable = false;

            $states[] = null === $availableAt || $availableAt <= $this->clock->now()
                ? UnlockState::unlockable($media)
                : UnlockState::locked($media, $availableAt);
        }

        return $states;
    }

    public function getMediaState(User $user, PackProgress $progress, Media $media): ?UnlockState
    {
        foreach ($this->getPackState($user, $progress) as $state) {
            if ($state->media->getId() === $media->getId()) {
                return $state;
            }
        }

        return null;
    }

    public function canOpen(User $user, PackProgress $progress, Media $media): bool
    {
        return $this->getMediaState($user, $progress, $media)?->isAccessible() ?? false;
    }

    /**
     * Date à laquelle le prochain média devient disponible, ou null s'il l'est déjà.
     */
    public function getNextAvailabilityDate(PackProgress $progress): ?\DateTimeImmutable
    {
        $lastOpenedAt = $progress->getLastOpenedAt();

        if (null === $lastOpenedAt) {
            return null;
        }

        return $lastOpenedAt->add(
            new \DateInterval(sprintf('PT%dH', $progress->getPack()->getUnlockDelayHours()))
        );
    }

    /**
     * @return array<int, true> ids des médias déjà ouverts, en clé pour un test O(1)
     */
    private function getOpenedMediaIds(User $user, Pack $pack): array
    {
        $ids = [];

        foreach ($this->mediaAccessRepository->findForUserAndPack($user, $pack) as $access) {
            $ids[$access->getMedia()->getId()] = true;
        }

        return $ids;
    }
}
