<?php

namespace App\Entity;

use App\Repository\FeedSkipRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un coup de pouce : le destinataire a sauté l'attente d'une notification.
 * Sert aux démonstrations, pour ne pas patienter des heures devant le chrono.
 */
#[ORM\Entity(repositoryClass: FeedSkipRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_skip_user_media', columns: ['user_id', 'media_id'])]
class FeedSkip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Media $media;

    #[ORM\Column]
    private \DateTimeImmutable $skippedAt;

    public function __construct()
    {
        $this->skippedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getMedia(): Media
    {
        return $this->media;
    }

    public function setMedia(Media $media): static
    {
        $this->media = $media;

        return $this;
    }

    public function getSkippedAt(): \DateTimeImmutable
    {
        return $this->skippedAt;
    }

    public function setSkippedAt(\DateTimeImmutable $skippedAt): static
    {
        $this->skippedAt = $skippedAt;

        return $this;
    }
}
