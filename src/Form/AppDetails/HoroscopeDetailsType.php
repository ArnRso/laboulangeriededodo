<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class HoroscopeDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sign', TextType::class, [
                'label' => 'Signe',
                'help' => 'Aussi repris dans l\'accroche du fil.',
                'constraints' => [new NotBlank()],
            ])
            ->add('ascendant', TextType::class, [
                'label' => 'Ascendant',
                'help' => 'Sous le signe. Un vrai ascendant ou un trait de caractère.',
                'required' => false,
            ])
            ->add('period', TextType::class, [
                'label' => 'Période',
                'help' => 'La date du thème, en haut de l\'écran.',
                'required' => false,
            ])
            ->add('predictionTitle', TextType::class, [
                'label' => 'Titre de la prédiction',
                'help' => 'Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('prediction', TextareaType::class, [
                'label' => 'Prédiction',
                'help' => 'Vide : reprend la description de la notification.',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('love', IntegerType::class, [
                'label' => 'Amour (sur 5)',
                'constraints' => [new Range(min: 0, max: 5)],
            ])
            ->add('work', IntegerType::class, [
                'label' => 'Travail (sur 5)',
                'constraints' => [new Range(min: 0, max: 5)],
            ])
            ->add('mood', IntegerType::class, [
                'label' => 'Humeur (sur 5)',
                'constraints' => [new Range(min: 0, max: 5)],
            ])
            ->add('compatibility', TextareaType::class, [
                'label' => 'Signes compatibles',
                'help' => 'Un par ligne, affichés en pastilles.',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "Gémeaux\nLion\nVerseau"],
            ])
            ->add('advice', TextareaType::class, [
                'label' => 'Conseil du jour',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'sign' => 'Balance',
            'ascendant' => 'ascendant drama',
            'period' => 'Aujourd\'hui · 23 août',
            'predictionTitle' => '',
            'prediction' => '',
            'love' => 4,
            'work' => 2,
            'mood' => 5,
            'compatibility' => 'Gémeaux
Lion
Verseau',
            'advice' => 'Les astres sont formels : ne réponds pas à ce message à 4h12. Tu vas le faire quand même.',
        ];
    }
}
