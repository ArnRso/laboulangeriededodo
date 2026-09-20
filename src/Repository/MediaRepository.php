<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * Le fil complet, brouillons compris, dans l'ordre de lecture.
     *
     * @return list<Media>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.position', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Le fil tel que le destinataire le parcourt.
     *
     * @return list<Media>
     */
    public function findPublishedOrdered(): array
    {
        // Un brouillon n'a pas d'écran : il n'existe pas pour le destinataire,
        // même si un jour la validation laissait passer un « published ».
        return $this->createQueryBuilder('m')
            ->andWhere('m.published = true')
            ->andWhere('m.appKind IS NOT NULL')
            ->orderBy('m.position', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Les étiquettes déjà posées, sans doublon et par ordre alphabétique :
     * de quoi les suggérer à la saisie et proposer un tri.
     *
     * @return list<string>
     */
    public function findUsedTags(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('m.tags')
            ->getQuery()
            ->getScalarResult();

        $tags = [];

        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $stored = $row['tags'] ?? [];

            // Selon le pilote, la colonne JSON revient décodée ou brute.
            if (\is_string($stored)) {
                $stored = json_decode($stored, true);
            }

            if (!\is_array($stored)) {
                continue;
            }

            foreach ($stored as $tag) {
                if (\is_string($tag) && '' !== $tag) {
                    $tags[mb_strtolower($tag)] = $tag;
                }
            }
        }

        ksort($tags);

        return array_values($tags);
    }

    public function findMaxPosition(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COALESCE(MAX(m.position), -1)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
