<?php

namespace App\Service\Dressing;

use App\Entity\Media;
use App\Enum\AppKind;
use App\Repository\MediaAccessRepository;

/**
 * Donne — ou change — l'application d'une notification à partir d'un
 * mapping, sans rien perdre au passage : les textes de l'app précédente que
 * le mapping ne reprend pas deviennent des fragments.
 */
final class NotificationDresser
{
    public function __construct(
        private readonly AppFieldCatalog $catalog,
        private readonly SourceCatalog $sources,
        private readonly AppDataComposer $composer,
        private readonly MediaAccessRepository $accessRepository,
    ) {
    }

    /**
     * On ne change que ce que le destinataire n'a pas encore vu.
     */
    public function canDress(Media $media): bool
    {
        return null === $media->getId() || [] === $this->accessRepository->findByMedia($media);
    }

    /**
     * Ne persiste rien : l'appelant enregistre, ou jette la copie s'il ne
     * voulait qu'un aperçu.
     */
    public function dress(Media $media, AppKind $target, DressMapping $mapping): void
    {
        // Les sources se lisent avant tout changement : l'app actuelle en fait partie.
        $sources = $this->sources->for($media);
        $composed = $this->composer->compose($this->catalog->fieldsFor($target), $sources, $mapping);

        $this->archiveAbandonedValues($media, $mapping);

        $media->setAppKind($target)->setAppData($composed);
    }

    /**
     * Seules les valeurs saisies comptent — pas les défauts de l'app, qui
     * rempliraient les fragments de « Dodo du passé » à chaque changement.
     */
    private function archiveAbandonedValues(Media $media, DressMapping $mapping): void
    {
        $previous = $media->getAppKind();

        if (null === $previous) {
            return;
        }

        $appData = $media->getAppData();

        foreach ($this->catalog->fieldsFor($previous) as $field) {
            $value = $appData[$field->name] ?? null;

            if (!\is_string($value) || '' === trim($value) || $value === $field->default) {
                continue;
            }

            if ($mapping->usesSource(sprintf('app:%s', $field->name))) {
                continue;
            }

            $media->addFragment(sprintf('%s · %s', $previous->label(), $field->label), $value);
        }
    }
}
