<?php

namespace App\Form;

use App\Service\Dressing\AppField;
use App\Service\Dressing\Source;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * D'où vient le texte d'un champ de l'app : une source parmi celles de la
 * notification, plusieurs pour un champ sur plusieurs lignes, ou un texte
 * libre — qui n'est retenu que s'il est choisi comme source.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class FieldMappingType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $field = $options['field'];
        \assert($field instanceof AppField);
        $sources = $options['sources'];
        \assert(\is_array($sources));

        $choices = [];

        foreach ($sources as $source) {
            \assert($source instanceof Source);
            $label = self::choiceLabel($source);

            // Deux sources au même libellé garderaient chacune leur entrée.
            if (isset($choices[$label])) {
                $label = sprintf('%s (%s)', $label, $source->key);
            }

            $choices[$label] = $source->key;
        }

        if ($field->kind->acceptsSeveralSources()) {
            $builder->add('sources', ChoiceType::class, [
                'label' => 'Sources, mises bout à bout',
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);
        } else {
            $builder->add('source', ChoiceType::class, [
                'label' => 'Source',
                'choices' => $choices,
                'placeholder' => 'Valeur par défaut de l\'app',
                'required' => false,
            ]);
        }

        $builder->add('custom', TextareaType::class, [
            'label' => 'Texte libre',
            'required' => false,
            'empty_data' => '',
            'attr' => ['rows' => 2],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['data_class' => null])
            ->setRequired(['field', 'sources'])
            ->setAllowedTypes('field', AppField::class)
            ->setAllowedTypes('sources', 'array');
    }

    private static function choiceLabel(Source $source): string
    {
        if ('' === $source->value) {
            return $source->label;
        }

        return sprintf('%s — « %s »', $source->label, $source->excerpt());
    }
}
