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
            ]);
    }

    public static function defaults(): array
    {
        return [
            'restaurant' => 'Chez Dodo',
            'rider' => 'Dodo du passé',
            'eta' => '11 ans',
            'items' => '',
        ];
    }
}
