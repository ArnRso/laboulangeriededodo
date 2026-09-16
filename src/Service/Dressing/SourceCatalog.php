<?php

namespace App\Service\Dressing;

use App\Entity\Media;
use App\Enum\MediaType;

/**
 * Tout ce qu'une notification a déjà comme texte, prêt à être versé dans
 * les champs d'une app : ses propres champs, ses fragments, et les valeurs
 * de l'app qu'elle porte peut-être déjà.
 */
final class SourceCatalog
{
    public const string CUSTOM = 'custom';

    public function __construct(
        private readonly AppFieldCatalog $catalog,
    ) {
    }

    /**
     * @return list<Source> dans un ordre stable : la notification, ses
     *                      fragments, l'app actuelle, puis le texte libre
     */
    public function for(Media $media): array
    {
        $sources = [new Source('title', 'Titre', $media->getTitle())];

        $description = $media->getDescription();
        if (null !== $description && '' !== trim($description)) {
            $sources[] = new Source('description', 'Description', $description);
        }

        $textContent = $media->getTextContent();
        if (MediaType::TEXT === $media->getType() && null !== $textContent && '' !== trim($textContent)) {
            $sources[] = new Source('memory', 'Souvenir (texte)', $textContent);
        }

        $url = $media->getUrl();
        if (MediaType::LINK === $media->getType() && null !== $url && '' !== trim($url)) {
            $sources[] = new Source('url', 'Lien', $url);
        }

        foreach ($media->getFragments() as $index => $fragment) {
            $source = new Source(sprintf('fragment:%d', $index), '', $fragment['text']);
            $label = '' !== $fragment['label'] ? $fragment['label'] : $source->excerpt();
            $sources[] = new Source($source->key, sprintf('Fragment · %s', $label), $source->value);
        }

        $appKind = $media->getAppKind();
        if (null !== $appKind) {
            $appData = $media->getAppData();

            foreach ($this->catalog->fieldsFor($appKind) as $field) {
                $value = $appData[$field->name] ?? null;

                // Les booléens n'ont pas de texte à offrir.
                if (\is_int($value) || (\is_string($value) && '' !== trim($value))) {
                    $sources[] = new Source(sprintf('app:%s', $field->name), sprintf('%s · %s', $appKind->label(), $field->label), (string) $value);
                }
            }
        }

        $sources[] = new Source(self::CUSTOM, 'Texte libre (ci-dessous)', '');

        return $sources;
    }
}
