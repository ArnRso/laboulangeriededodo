<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DeliverooDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('restaurant', TextType::class, [
                'label' => 'Restaurant',
                'help' => 'Le nom cité dans l\'accroche « Ta commande … est livrée ».',
                'constraints' => [new NotBlank()],
            ])
            ->add('rider', TextType::class, [
                'label' => 'Livreur',
                'required' => false,
            ])
            ->add('eta', TextType::class, [
                'label' => 'Temps de livraison affiché',
                'required' => false,
            ])
            ->add('items', TextareaType::class, [
                'label' => 'Articles',
                'help' => 'Un par ligne.',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "1× Canon event sauce piquante\n1× Side quest (supplément regrets)"],
            ])
            ->add('dishName', TextType::class, [
                'label' => 'Nom du plat',
                'help' => 'Dans « Ta commande ». Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('dishRestaurant', TextType::class, [
                'label' => 'Restaurant sous le plat',
                'help' => 'La petite ligne sous le nom du plat. Vide : reprend le restaurant.',
                'required' => false,
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Instructions de livraison',
                'help' => 'Vide : reprend la description de la notification.',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'restaurant' => 'Chez Dodo',
            'rider' => 'le.pot.agé',
            'eta' => '11 ans',
            'items' => '',
            'dishName' => '',
            'dishRestaurant' => '',
            'instructions' => '',
        ];
    }
}
