<?php

namespace App\Service;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les étiquettes de rangement : les créer, les renommer, les retirer.
 *
 * Deux étiquettes ne peuvent pas porter le même nom à la casse près, sans
 * quoi « Voyage » et « voyage » rangeraient la même chose à deux endroits.
 */
readonly class TagManager
{
    public function __construct(
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Une étiquette porte-t-elle déjà ce nom ? L'étiquette en cours de
     * modification ne se fait pas concurrence à elle-même.
     */
    public function nameIsTaken(Tag $tag): bool
    {
        $existing = $this->tagRepository->findOneByName($tag->getName());

        return null !== $existing && $existing !== $tag;
    }

    public function save(Tag $tag): void
    {
        $this->entityManager->persist($tag);
        $this->entityManager->flush();
    }

    /**
     * Retire l'étiquette, et d'abord des notifications qui la portent :
     * elles restent, seul le rangement disparaît.
     */
    public function delete(Tag $tag): void
    {
        foreach ($tag->getMedias() as $media) {
            $media->removeTag($tag);
        }

        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }
}
