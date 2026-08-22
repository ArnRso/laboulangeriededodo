<?php

namespace App\Service;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Composition du fil par l'administration : ajout, retrait, réordonnancement.
 *
 * Les positions sont toujours normalisées en une suite contiguë partant de
 * zéro, pour que l'ordre d'arrivée reste prévisible après toute opération.
 */
class FeedManager
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function add(Media $media): void
    {
        $media->setPosition($this->mediaRepository->findMaxPosition() + 1);

        $this->entityManager->persist($media);
        $this->entityManager->flush();
    }

    public function update(Media $media): void
    {
        $this->entityManager->flush();
    }

    public function delete(Media $media): void
    {
        $this->entityManager->remove($media);
        $this->entityManager->flush();

        $this->normalizePositions();
    }

    /**
     * Déplace une notification d'un cran, sans effet si elle est à l'extrémité.
     */
    public function move(Media $media, int $offset): void
    {
        $medias = $this->mediaRepository->findAllOrdered();
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

        [$medias[$currentIndex], $medias[$targetIndex]] = [$medias[$targetIndex], $medias[$currentIndex]];

        foreach ($medias as $position => $item) {
            $item->setPosition($position);
        }

        $this->entityManager->flush();
    }

    public function normalizePositions(): void
    {
        foreach ($this->mediaRepository->findAllOrdered() as $position => $media) {
            $media->setPosition($position);
        }

        $this->entityManager->flush();
    }
}
