<?php

namespace App\Tests\Factory;

use App\Entity\Media;
use App\Entity\Tag;
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

    /**
     * @param list<Tag> $tags
     */
    public function createNotification(
        int $position = 0,
        string $title = 'Notification de test',
        AppKind $appKind = AppKind::UBER_EATS,
        int $delayMinutes = 1440,
        bool $published = true,
        MediaType $type = MediaType::TEXT,
        array $tags = [],
    ): Media {
        $media = new Media();
        $media->setPosition($position)
            ->setTitle($title)
            ->setAppKind($appKind)
            ->setDelayMinutes($delayMinutes)
            ->setPublished($published)
            ->setType($type);

        foreach ($tags as $tag) {
            $media->addTag($tag);
        }

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
     * Une notification sans application ni souvenir : remplie d'abord,
     * habillée plus tard.
     *
     * @param list<array{label: string, text: string}> $fragments
     * @param list<Tag> $tags
     */
    public function createDraft(
        int $position = 0,
        string $title = 'Brouillon',
        ?string $description = null,
        array $fragments = [],
        array $tags = [],
    ): Media {
        $media = new Media();
        $media->setPosition($position)
            ->setTitle($title)
            ->setDescription($description)
            ->setFragments($fragments)
            ->setPublished(false);

        foreach ($tags as $tag) {
            $media->addTag($tag);
        }

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    public function createTag(string $name): Tag
    {
        $tag = new Tag();
        $tag->setName($name);

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
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
