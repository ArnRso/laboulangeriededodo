<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Range;

class SnapchatDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sender', TextType::class, [
                'label' => 'Expéditeur du Snap',
                'constraints' => [new NotBlank()],
            ])
            ->add('streak', IntegerType::class, [
                'label' => 'Jours de flamme',
                'help' => 'Le « 🔥 N » à côté du nom.',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('caption', TextType::class, [
                'label' => 'Légende sur le Snap',
                'help' => 'Le bandeau noir posé sur la photo. Laisse vide pour ne rien afficher.',
                'required' => false,
            ])
            ->add('timer', IntegerType::class, [
                'label' => 'Compteur (secondes)',
                'constraints' => [new Range(min: 1, max: 60)],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'sender' => 'Dodo du passé',
            'streak' => 4015,
            'caption' => 'leaked footage 👀',
            'timer' => 10,
        ];
    }
}
