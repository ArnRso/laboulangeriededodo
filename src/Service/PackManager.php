<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\Pack;
use App\Repository\MediaRepository;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Création, suppression et réordonnancement des packs et de leurs médias.
 *
 * Les positions sont toujours normalisées en une suite contiguë partant de zéro,
 * pour que l'ordre de lecture reste prévisible après n'importe quelle opération.
 */
readonly class PackManager
{
    public function __construct(
        private PackRepository $packRepository,
        private MediaRepository $mediaRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createPack(Pack $pack): void
    {
        $pack->setPosition($this->packRepository->findMaxPosition() + 1);

        $this->entityManager->persist($pack);
        $this->entityManager->flush();
    }

    public function updatePack(): void
    {
        $this->entityManager->flush();
    }

    public function deletePack(Pack $pack): void
    {
        // Les médias sont retirés explicitement : la cascade Doctrine ne joue que
        // sur la collection déjà chargée en mémoire.
        foreach ($this->mediaRepository->findByPackOrdered($pack) as $media) {
            $this->entityManager->remove($media);
        }

        $this->entityManager->remove($pack);
        $this->entityManager->flush();

        $this->normalizePackPositions();
    }

    public function addMedia(Pack $pack, Media $media): void
    {
        $media->setPack($pack);
        $media->setPosition($this->mediaRepository->findMaxPosition($pack) + 1);

        $this->entityManager->persist($media);
        $this->entityManager->flush();
    }

    public function updateMedia(): void
    {
        $this->entityManager->flush();
    }

    public function deleteMedia(Media $media): void
    {
        $pack = $media->getPack();

        $this->entityManager->remove($media);
        $this->entityManager->flush();

        $this->normalizeMediaPositions($pack);
    }

    /**
     * Déplace un média d'un cran, sans effet s'il est déjà à l'extrémité.
     */
    public function moveMedia(Media $media, int $offset): void
    {
        $medias = $this->mediaRepository->findByPackOrdered($media->getPack());
        $currentIndex = null;

        foreach ($medias as $index => $candidate) {
            if ($candidate === $media) {
                $currentIndex = $index;

                break;
            }
        }

        if (null === $currentIndex) {
            return;
        }

        $targetIndex = $currentIndex + $offset;

        if ($targetIndex < 0 || $targetIndex >= \count($medias)) {
            return;
        }

        $reordered = $medias;
        [$reordered[$currentIndex], $reordered[$targetIndex]] = [$reordered[$targetIndex], $reordered[$currentIndex]];

        foreach ($reordered as $position => $item) {
            $item->setPosition($position);
        }

        $this->entityManager->flush();
    }

    /**
     * Réattribue des positions contiguës aux médias d'un pack.
     */
    public function normalizeMediaPositions(Pack $pack): void
    {
        foreach ($this->mediaRepository->findByPackOrdered($pack) as $position => $media) {
            $media->setPosition($position);
        }

        $this->entityManager->flush();
    }

    /**
     * Réattribue des positions contiguës aux packs.
     */
    public function normalizePackPositions(): void
    {
        foreach ($this->packRepository->findAllOrdered() as $position => $pack) {
            $pack->setPosition($position);
        }

        $this->entityManager->flush();
    }
}
