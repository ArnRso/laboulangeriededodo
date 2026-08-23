<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class UberDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('driver', TextType::class, [
                'label' => 'Chauffeur',
                'help' => 'Qui a conduit Dorian jusqu\'ici.',
                'constraints' => [new NotBlank()],
            ])
            ->add('car', TextType::class, [
                'label' => 'Voiture',
                'help' => 'Modèle et plaque, comme sur la carte du chauffeur.',
                'required' => false,
            ])
            ->add('fromPlace', TextType::class, [
                'label' => 'Départ',
                'required' => false,
            ])
            ->add('toPlace', TextType::class, [
                'label' => 'Arrivée',
                'required' => false,
            ])
            ->add('duration', TextType::class, [
                'label' => 'Durée de la course',
                'required' => false,
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Note donnée au chauffeur',
                'choices' => [
                    '★☆☆☆☆' => 1,
                    '★★☆☆☆' => 2,
                    '★★★☆☆' => 3,
                    '★★★★☆' => 4,
                    '★★★★★' => 5,
                ],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'driver' => 'Dodo du passé',
            'car' => 'Peugeot 206 · AB-123-CD',
            'fromPlace' => 'Ton adolescence',
            'toPlace' => 'Aujourd\'hui',
            'duration' => '11 ans',
            'rating' => 5,
        ];
    }
}
