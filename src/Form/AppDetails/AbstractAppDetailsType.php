<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de détails d'une application imitée. Les données vivent dans le
 * JSON `appData` du média : chaque type déclare ses champs et les valeurs qui
 * les préremplissent à la création.
 *
 * @extends AbstractType<mixed>
 */
abstract class AbstractAppDetailsType extends AbstractType
{
    /**
     * Valeurs proposées à la création, pour que le formulaire parle déjà la
     * langue de l'app et que l'admin n'ait qu'à personnaliser.
     *
     * @return array<string, mixed>
     */
    abstract public static function defaults(): array;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
