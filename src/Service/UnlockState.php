<?php

namespace App\Service;

use App\Entity\Media;

/**
 * État de déblocage d'un média pour un utilisateur donné, à un instant donné.
 */
final readonly class UnlockState
{
    private function __construct(
        public Media $media,
        public bool $opened,
        public bool $unlockable,
        public ?\DateTimeImmutable $availableAt,
    ) {
    }

    public static function opened(Media $media): self
    {
        return new self($media, true, true, null);
    }

    public static function unlockable(Media $media): self
    {
        return new self($media, false, true, null);
    }

    public static function locked(Media $media, ?\DateTimeImmutable $availableAt): self
    {
        return new self($media, false, false, $availableAt);
    }

    /**
     * Un média est consultable s'il a déjà été ouvert ou s'il peut l'être maintenant.
     */
    public function isAccessible(): bool
    {
        return $this->opened || $this->unlockable;
    }
}
