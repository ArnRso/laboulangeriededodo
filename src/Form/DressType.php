<?php

namespace App\Form;

use App\Service\Dressing\AppField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * L'habillage d'une notification en une app : un mapping par champ de
 * l'app cible, construit depuis son catalogue de champs.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class DressType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fields = $options['fields'];
        \assert(\is_array($fields));

        $builder->add('fields', FormType::class, ['label' => false]);
        $group = $builder->get('fields');

        foreach ($fields as $field) {
            \assert($field instanceof AppField);

            $group->add($field->name, FieldMappingType::class, [
                'label' => false,
                'field' => $field,
                'sources' => $options['sources'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['data_class' => null])
            ->setRequired(['fields', 'sources'])
            ->setAllowedTypes('fields', 'array')
            ->setAllowedTypes('sources', 'array');
    }
}
