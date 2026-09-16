<?php

namespace App\Service\Dressing;

use App\Enum\AppFieldKind;

/**
 * Fabrique les détails d'une app à partir d'un mapping : chaque champ reçoit
 * le texte des sources choisies, converti à sa nature, ou son défaut si rien
 * ne lui est destiné. Pur : ni base, ni formulaire, ni entité.
 */
final class AppDataComposer
{
    /**
     * @param list<AppField> $fields
     * @param list<Source> $sources
     *
     * @return array<string, mixed> exactement les clés des champs, dans leur ordre
     */
    public function compose(array $fields, array $sources, DressMapping $mapping): array
    {
        $composed = [];

        foreach ($fields as $field) {
            $composed[$field->name] = self::convert($field, self::texts($sources, $mapping->for($field->name)));
        }

        return $composed;
    }

    /**
     * Les textes choisis, dans l'ordre du catalogue et non celui du clic :
     * l'admin retrouve toujours le titre avant la description, les fragments
     * dans leur ordre, le texte libre en dernier.
     *
     * @param list<Source> $sources
     *
     * @return list<string>
     */
    private static function texts(array $sources, FieldMapping $mapping): array
    {
        $texts = [];

        foreach ($sources as $source) {
            if (!$mapping->usesSource($source->key)) {
                continue;
            }

            $text = trim(SourceCatalog::CUSTOM === $source->key ? $mapping->custom : $source->value);

            if ('' !== $text) {
                $texts[] = $text;
            }
        }

        return $texts;
    }

    /**
     * @param list<string> $texts
     */
    private static function convert(AppField $field, array $texts): mixed
    {
        if ([] === $texts) {
            return $field->default;
        }

        return match ($field->kind) {
            AppFieldKind::TEXTAREA => implode("\n", $texts),
            AppFieldKind::TEXT => $texts[0],
            AppFieldKind::INTEGER => self::integer($texts[0]) ?? $field->default,
            AppFieldKind::CHECKBOX => self::boolean($texts[0]) ?? $field->default,
            AppFieldKind::CHOICE => self::choice($field, $texts[0]) ?? $field->default,
        };
    }

    private static function integer(string $text): ?int
    {
        $value = filter_var($text, \FILTER_VALIDATE_INT);

        return false === $value ? null : $value;
    }

    private static function boolean(string $text): ?bool
    {
        return match (mb_strtolower($text)) {
            'oui', 'vrai', '1', 'true', 'on' => true,
            'non', 'faux', '0', 'false', 'off' => false,
            default => null,
        };
    }

    /**
     * Une valeur du champ, ou le libellé d'un de ses choix.
     */
    private static function choice(AppField $field, string $text): int|string|null
    {
        foreach ($field->choices as $label => $value) {
            if ($text === (string) $value || $text === $label) {
                return $value;
            }
        }

        return null;
    }
}
