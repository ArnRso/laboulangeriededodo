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
 *  - le suivant se débloque `unlockDelayMinutes` après la dernière ouverture ;
 *  - un média déjà ouvert le reste définitivement.
 */
readonly class UnlockService
{
    public function __construct(
        private MediaRepository $mediaRepository,
        private MediaAccessRepository $mediaAccessRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return UnlockState[] indexés par position dans le pack
     *
     * @throws \DateMalformedIntervalStringException
     */
    public function getPackState(User $user, PackProgress $progress): array
    {
        $pack = $progress->getPack();
        $medias = $this->mediaRepository->findByPackOrdered($pack);
        $openedMedias = $this->getOpenedMedias($user, $pack);
        $availableAt = $this->getNextAvailabilityDate($progress);

        $states = [];
        $nextIsUnlockable = true;

        foreach ($medias as $media) {
            if (isset($openedMedias[spl_object_id($media)])) {
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

    /**
     * @throws \DateMalformedIntervalStringException
     */
    public function getMediaState(User $user, PackProgress $progress, Media $media): ?UnlockState
    {
        return array_find($this->getPackState($user, $progress), fn ($state) => $state->media === $media);
    }

    /**
     * @throws \DateMalformedIntervalStringException
     */
    public function canOpen(User $user, PackProgress $progress, Media $media): bool
    {
        return $this->getMediaState($user, $progress, $media)?->isAccessible() ?? false;
    }

    /**
     * Date à laquelle le prochain média devient disponible, ou null s'il l'est déjà.
     *
     * @throws \DateMalformedIntervalStringException
     */
    public function getNextAvailabilityDate(PackProgress $progress): ?\DateTimeImmutable
    {
        return $progress->getLastOpenedAt()?->add(
            new \DateInterval(sprintf('PT%dM', $progress->getPack()->getUnlockDelayMinutes()))
        );
    }

    /**
     * Médias déjà ouverts, indexés par identifiant d'objet pour un test O(1).
     *
     * L'identité d'objet est utilisée plutôt que l'id afin de rester correct
     * même sur des entités qui ne sont pas encore persistées.
     *
     * @return array<int, true>
     */
    private function getOpenedMedias(User $user, Pack $pack): array
    {
        $opened = [];

        foreach ($this->mediaAccessRepository->findForUserAndPack($user, $pack) as $access) {
            $opened[spl_object_id($access->getMedia())] = true;
        }

        return $opened;
    }
}
