<?php

namespace App\Tests\Factory;

use App\Entity\Media;
use App\Entity\Pack;
use App\Enum\MediaType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée packs et médias pour les tests, avec des positions déjà contiguës.
 */
final readonly class PackFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createPack(
        string $name = 'Pack de test',
        int $unlockDelayHours = 24,
        bool $published = true,
        int $position = 0,
    ): Pack {
        $pack = new Pack();
        $pack->setName($name)
            ->setDescription('Description de test')
            ->setUnlockDelayHours($unlockDelayHours)
            ->setPublished($published)
            ->setPosition($position);

        $this->entityManager->persist($pack);
        $this->entityManager->flush();

        return $pack;
    }

    public function createMedia(
        Pack $pack,
        int $position = 0,
        string $title = 'Média de test',
        MediaType $type = MediaType::TEXT,
    ): Media {
        $media = new Media();
        $media->setPack($pack)
            ->setPosition($position)
            ->setTitle($title)
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
     * @return list<Media>
     */
    public function createMedias(Pack $pack, int $count): array
    {
        $medias = [];

        for ($i = 0; $i < $count; ++$i) {
            $medias[] = $this->createMedia($pack, $i, sprintf('Média %d', $i + 1));
        }

        return $medias;
    }
}
