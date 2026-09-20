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
            ->add('orderTitle', TextType::class, [
                'label' => 'Titre de la commande',
                'help' => 'Le grand titre en haut de l\'écran. Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('itemName', TextType::class, [
                'label' => 'Nom de l\'article',
                'help' => 'La ligne « 1× … » dans « Ta commande ». Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('itemNote', TextType::class, [
                'label' => 'Description de l\'article',
                'help' => 'Sous le nom de l\'article. Vide : reprend la description de la notification.',
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
            'courier' => 'le.pot.agé',
            'trip' => 'Ton adolescence → Aujourd\'hui · 11 ans de trajet',
            'orderTitle' => '',
            'itemName' => '',
            'itemNote' => '',
            'stars' => 5,
        ];
    }
}
