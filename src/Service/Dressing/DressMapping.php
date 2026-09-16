<?php

namespace App\Service\Dressing;

/**
 * Le mapping complet d'un habillage : pour chaque champ de l'app cible, d'où
 * vient son texte. Construit depuis les données brutes du formulaire, avec
 * assez de gardes pour ne jamais faire confiance à leur forme.
 */
final readonly class DressMapping
{
    /**
     * @param array<string, FieldMapping> $fields
     */
    private function __construct(
        public array $fields,
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Attend la forme du formulaire d'habillage :
     * `['fields' => [nom => ['source' => ?, 'sources' => [?], 'custom' => ?]]]`.
     * Un champ absent ou mal formé compte comme « laisser le défaut ».
     */
    public static function fromFormData(mixed $data): self
    {
        if (!\is_array($data) || !\is_array($data['fields'] ?? null)) {
            return self::empty();
        }

        $fields = [];

        foreach ($data['fields'] as $name => $entry) {
            if (!\is_string($name) || !\is_array($entry)) {
                continue;
            }

            $sources = [];

            if (\is_string($entry['source'] ?? null) && '' !== $entry['source']) {
                $sources[] = $entry['source'];
            }

            if (\is_array($entry['sources'] ?? null)) {
                foreach ($entry['sources'] as $key) {
                    if (\is_string($key) && '' !== $key) {
                        $sources[] = $key;
                    }
                }
            }

            $custom = $entry['custom'] ?? '';

            $fields[$name] = new FieldMapping(array_values(array_unique($sources)), \is_string($custom) ? $custom : '');
        }

        return new self($fields);
    }

    public function for(string $field): FieldMapping
    {
        return $this->fields[$field] ?? new FieldMapping();
    }

    public function usesSource(string $key): bool
    {
        foreach ($this->fields as $mapping) {
            if ($mapping->usesSource($key)) {
                return true;
            }
        }

        return false;
    }
}
