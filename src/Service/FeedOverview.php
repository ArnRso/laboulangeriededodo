<?php

namespace App\Service;

/**
 * Le fil vu par le destinataire : au plus une notification fraîche, au plus
 * une en route, et celles déjà consultées.
 */
final readonly class FeedOverview
{
    /**
     * @param list<NotificationState> $seen de la plus récente à la plus ancienne
     */
    public function __construct(
        public ?NotificationState $fresh,
        public ?NotificationState $next,
        public array $seen,
    ) {
    }

    public function isFinished(): bool
    {
        return null === $this->fresh && null === $this->next && [] !== $this->seen;
    }

    public function isEmpty(): bool
    {
        return null === $this->fresh && null === $this->next && [] === $this->seen;
    }
}
