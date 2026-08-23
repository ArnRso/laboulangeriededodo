<?php

namespace App\Service;

use App\Entity\Media;
use App\Repository\FeedSkipRepository;
use App\Repository\MediaAccessRepository;
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
        private readonly MediaAccessRepository $mediaAccessRepository,
        private readonly FeedSkipRepository $feedSkipRepository,
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
        // La base efface consultations et coups de pouce en cascade, mais
        // Doctrine garde en mémoire ceux qu'il avait chargés : sans cet oubli,
        // il tenterait de les réinsérer au flush de la renumérotation. On les
        // relève avant la suppression, tant qu'ils existent encore.
        $orphans = [
            ...$this->mediaAccessRepository->findByMedia($media),
            ...$this->feedSkipRepository->findByMedia($media),
        ];

        $this->entityManager->remove($media);
        $this->entityManager->flush();

        foreach ($orphans as $orphan) {
            $this->entityManager->detach($orphan);
        }

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
