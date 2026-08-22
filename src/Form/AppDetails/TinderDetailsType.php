<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @extends AbstractType<mixed>
 */
class TinderDetailsType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matchName', TextType::class, [
                'label' => 'Nom du match',
                'help' => 'Avec qui Dorian a matché — une personne, un objet, une décision.',
                'constraints' => [new NotBlank()],
            ])
            ->add('matchAge', IntegerType::class, [
                'label' => 'Âge affiché',
                'constraints' => [new Range(min: 1, max: 150)],
            ])
            ->add('matchEmoji', TextType::class, [
                'label' => 'Emoji du match',
                'help' => 'Sert d\'avatar quand la notification n\'a pas de photo.',
                'required' => false,
            ])
            ->add('locationLine', TextType::class, [
                'label' => 'Ligne de localisation',
                'required' => false,
            ])
            ->add('dramaLevel', IntegerType::class, [
                'label' => 'Drama level (%)',
                'constraints' => [new Range(min: 0, max: 100)],
            ])
            ->add('chips', TextareaType::class, [
                'label' => 'Étiquettes',
                'help' => 'Une par ligne. La première est mise en rouge.',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
