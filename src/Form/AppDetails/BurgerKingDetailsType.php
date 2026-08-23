<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BurgerKingDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderNumber', TextType::class, [
                'label' => 'Numéro de commande',
                'help' => 'Affiché en gros sur le ticket — une année, un âge, un chiffre qui parle.',
                'constraints' => [new NotBlank()],
            ])
            ->add('restaurant', TextType::class, [
                'label' => 'Restaurant',
                'required' => false,
            ])
            ->add('items', TextareaType::class, [
                'label' => 'Articles',
                'help' => 'Un par ligne. Le total du ticket est l\'aura de la notification.',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "1× Whopper du drama\n1× Frites de la honte"],
            ])
            ->add('crownLine', TextType::class, [
                'label' => 'Phrase de la couronne',
                'help' => 'Le titre décerné dans l\'encart 👑.',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'orderNumber' => '2015',
            'restaurant' => 'Burger King · Ton adolescence',
            'items' => '',
            'crownLine' => 'Roi du drama du jour',
        ];
    }
}
