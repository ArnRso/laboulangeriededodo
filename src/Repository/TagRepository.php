<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Les étiquettes par ordre alphabétique, comme on les lit : « Éclair »
     * se range entre « Do » et « Fa », ce dont le tri de la base ne se
     * charge pas sans collation particulière.
     *
     * @return list<Tag>
     */
    public function findAllOrdered(): array
    {
        /** @var list<Tag> $tags */
        $tags = $this->createQueryBuilder('t')
            ->getQuery()
            ->getResult();

        $collator = new \Collator('fr_FR');
        // compare() rend false s'il échoue : l'ordre reste alors inchangé.
        usort($tags, static function (Tag $a, Tag $b) use ($collator): int {
            $comparison = $collator->compare($a->getName(), $b->getName());

            return false === $comparison ? 0 : $comparison;
        });

        return $tags;
    }

    /**
     * L'étiquette portant ce nom, quelle que soit la casse saisie : deux
     * variantes de « voyage » ne doivent pas cohabiter.
     */
    public function findOneByName(string $name): ?Tag
    {
        $tag = $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.name) = LOWER(:name)')
            ->setParameter('name', trim($name))
            ->getQuery()
            ->getOneOrNullResult();

        return $tag instanceof Tag ? $tag : null;
    }
}
