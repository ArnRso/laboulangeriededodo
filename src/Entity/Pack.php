<?php

namespace App\Entity;

use App\Repository\PackRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PackRepository::class)]
class Pack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Le délai est stocké en minutes : le formulaire le saisit en heures et
     * minutes, mais une valeur unique évite d'avoir deux champs à recombiner
     * et à maintenir cohérents.
     */
    #[ORM\Column]
    #[Assert\Positive(message: 'Le délai doit être d\'au moins une minute.')]
    private int $unlockDelayMinutes = 1440;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $published = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Media>
     */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'pack', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $medias;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUnlockDelayMinutes(): int
    {
        return $this->unlockDelayMinutes;
    }

    public function setUnlockDelayMinutes(int $unlockDelayMinutes): static
    {
        $this->unlockDelayMinutes = $unlockDelayMinutes;

        return $this;
    }

    /**
     * Part entière en heures du délai, pour l'affichage et la saisie.
     */
    public function getDelayHoursPart(): int
    {
        return intdiv($this->unlockDelayMinutes, 60);
    }

    /**
     * Minutes restantes une fois les heures retirées.
     */
    public function getDelayMinutesPart(): int
    {
        return $this->unlockDelayMinutes % 60;
    }

    /**
     * Délai formaté pour l'affichage, par exemple « 1 h 30 » ou « 45 min ».
     */
    public function getDelayLabel(): string
    {
        $hours = $this->getDelayHoursPart();
        $minutes = $this->getDelayMinutesPart();

        if (0 === $hours) {
            return sprintf('%d min', $minutes);
        }

        if (0 === $minutes) {
            return sprintf('%d h', $hours);
        }

        return sprintf('%d h %02d', $hours, $minutes);
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setPack($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): static
    {
        // orphanRemoval se charge de la suppression en base.
        $this->medias->removeElement($media);

        return $this;
    }

    public function getMediaCount(): int
    {
        return $this->medias->count();
    }

    public function getMediaAtPosition(int $position): ?Media
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('position', $position))
            ->setMaxResults(1);

        $media = $this->medias->matching($criteria)->first();

        return false === $media ? null : $media;
    }
}
