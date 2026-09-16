<?php

namespace App\Service\Dressing;

/**
 * Un texte que la notification a déjà — titre, fragment, valeur de l'app
 * précédente… — et que l'admin peut verser dans n'importe quel champ.
 */
final readonly class Source
{
    public function __construct(
        public string $key,
        public string $label,
        public string $value,
    ) {
    }

    /**
     * Le début du texte, pour reconnaître la source dans une liste sans la
     * dérouler.
     */
    public function excerpt(int $length = 40): string
    {
        $flat = trim((string) preg_replace('/\s+/', ' ', $this->value));

        if (mb_strlen($flat) <= $length) {
            return $flat;
        }

        return rtrim(mb_substr($flat, 0, $length)).'…';
    }
}
