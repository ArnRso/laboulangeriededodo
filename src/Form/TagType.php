<?php

namespace App\Form;

use App\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Une étiquette de rangement.
 *
 * @extends AbstractType<Tag>
 */
class TagType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Nom',
            // Le setter est typé : un champ vidé doit arriver comme une
            // chaîne vide, que la validation refusera.
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'Souvenirs, à retravailler, blague…',
                'maxlength' => Tag::MAX_NAME_LENGTH,
                'autofocus' => true,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Tag::class]);
    }
}
