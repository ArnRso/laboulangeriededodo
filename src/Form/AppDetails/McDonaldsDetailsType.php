<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class McDonaldsDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderCode', TextType::class, [
                'label' => 'Code de commande',
                'help' => 'Le code à lettre et chiffres affiché sur l\'écran du restaurant, ex. « D42 ».',
                'constraints' => [new NotBlank()],
            ])
            ->add('restaurant', TextType::class, [
                'label' => 'Restaurant',
                'required' => false,
            ])
            ->add('items', TextareaType::class, [
                'label' => 'Articles',
                'help' => 'Un par ligne.',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "1× Happy Meal de la nostalgie\n1× McFlurry des regrets"],
            ])
            ->add('slogan', TextType::class, [
                'label' => 'Slogan en bas de l\'écran',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'orderCode' => 'D42',
            'restaurant' => 'McDonald\'s · Ton adolescence',
            'items' => '',
            'slogan' => 'C\'est tout ce que j\'aime',
        ];
    }
}
