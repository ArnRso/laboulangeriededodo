<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class UberEatsDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('courier', TextType::class, [
                'label' => 'Livreur',
                'help' => 'Qui a livré la commande.',
                'constraints' => [new NotBlank()],
            ])
            ->add('trip', TextType::class, [
                'label' => 'Trajet',
                'help' => 'Affiché sous la carte, par exemple « Ton adolescence → Aujourd\'hui · 11 ans de trajet ».',
                'required' => false,
            ])
            ->add('stars', ChoiceType::class, [
                'label' => 'Note de la commande',
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
            'courier' => 'Dodo du passé',
            'trip' => 'Ton adolescence → Aujourd\'hui · 11 ans de trajet',
            'stars' => 5,
        ];
    }
}
