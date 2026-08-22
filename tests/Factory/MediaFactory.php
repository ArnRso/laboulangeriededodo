<?php

namespace App\Tests\Factory;

use App\Entity\Media;
use App\Enum\AppKind;
use App\Enum\MediaType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée des notifications pour les tests, avec des positions déjà contiguës.
 */
final readonly class MediaFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createNotification(
        int $position = 0,
        string $title = 'Notification de test',
        AppKind $appKind = AppKind::UBER_EATS,
        int $delayMinutes = 1440,
        int $auraPoints = 100,
        bool $published = true,
        MediaType $type = MediaType::TEXT,
    ): Media {
        $media = new Media();
        $media->setPosition($position)
            ->setTitle($title)
            ->setAppKind($appKind)
            ->setDelayMinutes($delayMinutes)
            ->setAuraPoints($auraPoints)
            ->setPublished($published)
            ->setType($type);

        if (MediaType::TEXT === $type) {
            $media->setTextContent('Contenu de test');
        } elseif (MediaType::LINK === $type) {
            $media->setUrl('https://example.com');
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    /**
     * Un fil de notifications au délai uniforme, titrées « Notification 1 », « Notification 2 »….
     *
     * @return list<Media>
     */
    public function createFeed(int $count, int $delayMinutes = 1440): array
    {
        $medias = [];

        for ($i = 0; $i < $count; ++$i) {
            $medias[] = $this->createNotification($i, sprintf('Notification %d', $i + 1), delayMinutes: $delayMinutes);
        }

        return $medias;
    }
}
