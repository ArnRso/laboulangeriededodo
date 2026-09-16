<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Un bout de texte gardé sous le coude, avec une étiquette pour s'y retrouver
 * au moment de le placer dans un champ de l'application.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class FragmentType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Étiquette',
                'required' => false,
                'empty_data' => '',
                'attr' => ['placeholder' => 'Commentaire de Marie, légende, punchline…'],
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Texte',
                'required' => false,
                'empty_data' => '',
                'attr' => ['rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
