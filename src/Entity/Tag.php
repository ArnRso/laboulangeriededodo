<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une étiquette de rangement, posée sur les notifications pour s'y retrouver
 * dans le back-office. Le destinataire ne les voit jamais.
 *
 * Deux étiquettes ne peuvent pas porter le même nom à la casse près : la base
 * le garantit par un index sur LOWER(name), que Doctrine ne sait pas décrire.
 */
#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    /**
     * Au-delà, l'étiquette n'aide plus à s'y retrouver.
     */
    public const int MAX_NAME_LENGTH = 32;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: self::MAX_NAME_LENGTH)]
    #[Assert\NotBlank(message: 'Donne un nom à l\'étiquette.')]
    #[Assert\Length(max: self::MAX_NAME_LENGTH)]
    private string $name = '';

    /**
     * @var Collection<int, Media>
     */
    #[ORM\ManyToMany(targetEntity: Media::class, mappedBy: 'tags')]
    private Collection $medias;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Les espaces en trop sont resserrés : « à  revoir » et « à revoir »
     * désignent la même étiquette, et l'unicité se joue sur le nom.
     */
    public function setName(string $name): static
    {
        $this->name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
