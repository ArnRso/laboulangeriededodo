<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\MediaAccess;
use App\Entity\Pack;
use App\Entity\PackProgress;
use App\Entity\User;
use App\Repository\MediaAccessRepository;
use App\Repository\MediaRepository;
use App\Repository\PackProgressRepository;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Pilote la progression du destinataire : choix d'un pack, ouverture des médias,
 * et passage au pack suivant une fois le précédent terminé.
 */
readonly class ProgressionService
{
    public function __construct(
        private PackRepository $packRepository,
        private PackProgressRepository $packProgressRepository,
        private MediaRepository $mediaRepository,
        private MediaAccessRepository $mediaAccessRepository,
        private UnlockService $unlockService,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Pack en cours, ou null si le destinataire doit en choisir un.
     */
    public function getActiveProgress(User $user): ?PackProgress
    {
        return $this->packProgressRepository->findActiveForUser($user);
    }

    /**
     * Packs qu'il peut choisir : publiés, non vides et pas encore commencés.
     *
     * @return list<Pack>
     */
    public function getSelectablePacks(User $user): array
    {
        $startedPacks = [];

        foreach ($this->packProgressRepository->findAllForUser($user) as $progress) {
            $startedPacks[spl_object_id($progress->getPack())] = true;
        }

        $selectable = [];

        foreach ($this->packRepository->findPublishedOrdered() as $pack) {
            if (isset($startedPacks[spl_object_id($pack)])) {
                continue;
            }

            // Un pack vide n'aurait rien à offrir et bloquerait la progression.
            if ([] === $this->mediaRepository->findByPackOrdered($pack)) {
                continue;
            }

            $selectable[] = $pack;
        }

        return $selectable;
    }

    /**
     * Démarre un pack.
     *
     * @throws \LogicException si un pack est déjà en cours ou si celui-ci est indisponible
     */
    public function startPack(User $user, Pack $pack): PackProgress
    {
        if (null !== $this->getActiveProgress($user)) {
            throw new \LogicException('Un pack est déjà en cours.');
        }

        if (!\in_array($pack, $this->getSelectablePacks($user), true)) {
            throw new \LogicException('Ce pack n\'est pas disponible.');
        }

        $progress = new PackProgress();
        $progress->setUser($user)
            ->setPack($pack)
            ->setStartedAt($this->clock->now());

        $this->entityManager->persist($progress);
        $this->entityManager->flush();

        return $progress;
    }

    /**
     * Enregistre l'ouverture d'un média et fait repartir le délai.
     *
     * @throws \LogicException si le média n'est pas encore accessible
     */
    public function openMedia(User $user, PackProgress $progress, Media $media): MediaAccess
    {
        $existing = $this->mediaAccessRepository->findOneByUserAndMedia($user, $media);

        if (null !== $existing) {
            return $existing;
        }

        if (!$this->unlockService->canOpen($user, $progress, $media)) {
            throw new \LogicException('Ce média n\'est pas encore accessible.');
        }

        $access = new MediaAccess();
        $access->setUser($user)
            ->setMedia($media)
            ->setOpenedAt($this->clock->now());

        $this->entityManager->persist($access);

        // Le délai du média suivant court à partir de cette ouverture.
        $progress->setLastOpenedAt($this->clock->now());

        if ($this->isPackFullyOpened($user, $progress)) {
            $progress->setCompletedAt($this->clock->now());
        }

        $this->entityManager->flush();

        return $access;
    }

    /**
     * @return list<PackProgress>
     */
    public function getCompletedProgresses(User $user): array
    {
        return $this->packProgressRepository->findCompletedForUser($user);
    }

    private function isPackFullyOpened(User $user, PackProgress $progress): bool
    {
        $pack = $progress->getPack();
        $total = \count($this->mediaRepository->findByPackOrdered($pack));

        // L'accès en cours n'est pas encore en base : il compte pour un.
        return $this->mediaAccessRepository->countForUserAndPack($user, $pack) + 1 >= $total;
    }
}
