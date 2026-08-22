<?php

namespace App\Service;

use App\Entity\Media;

/**
 * État d'une notification du fil pour le destinataire, à un instant donné.
 */
final readonly class NotificationState
{
    private function __construct(
        public Media $media,
        public bool $opened,
        public ?\DateTimeImmutable $openedAt,
        public bool $unlockable,
        public ?\DateTimeImmutable $availableAt,
    ) {
    }

    public static function opened(Media $media, \DateTimeImmutable $openedAt): self
    {
        return new self($media, true, $openedAt, true, null);
    }

    public static function unlockable(Media $media): self
    {
        return new self($media, false, null, true, null);
    }

    public static function locked(Media $media, ?\DateTimeImmutable $availableAt): self
    {
        return new self($media, false, null, false, $availableAt);
    }

    /**
     * Consultable : déjà ouverte, ou ouvrable maintenant.
     */
    public function isAccessible(): bool
    {
        return $this->opened || $this->unlockable;
    }

    /**
     * La prochaine à arriver, dont le chrono court ou courra.
     */
    public function isNext(): bool
    {
        return !$this->opened && !$this->unlockable && null !== $this->availableAt;
    }
}
