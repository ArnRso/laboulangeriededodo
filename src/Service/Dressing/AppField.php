<?php

namespace App\Service\Dressing;

use App\Enum\AppFieldKind;

/**
 * Un champ de détails d'une app, vu par l'habillage : ce qu'il s'appelle,
 * comment il se présente à l'admin, et ce qu'il vaut quand rien ne le remplit.
 */
final readonly class AppField
{
    /**
     * @param array<string, int|string> $choices libellé => valeur, pour les champs à choix
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $help,
        public AppFieldKind $kind,
        public bool $required,
        public mixed $default,
        public array $choices = [],
    ) {
    }
}
