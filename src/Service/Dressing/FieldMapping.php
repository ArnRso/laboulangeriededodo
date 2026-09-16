<?php

namespace App\Service\Dressing;

/**
 * Ce que l'admin a choisi de verser dans un champ : des clés de sources, et
 * le texte libre si la source « custom » en fait partie.
 */
final readonly class FieldMapping
{
    /**
     * @param list<string> $sources
     */
    public function __construct(
        public array $sources = [],
        public string $custom = '',
    ) {
    }

    public function usesSource(string $key): bool
    {
        return \in_array($key, $this->sources, true);
    }
}
