<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class BumbleDetailsType extends AbstractAppDetailsType
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
            ->add('age', IntegerType::class, [
                'label' => 'Âge affiché',
                'constraints' => [new Range(min: 1, max: 150)],
            ])
            ->add('hoursLeft', IntegerType::class, [
                'label' => 'Heures restantes',
                'help' => 'Pour faire le premier pas, de 0 à 24.',
                'constraints' => [new Range(min: 0, max: 24)],
            ])
            ->add('tagline', TextType::class, [
                'label' => 'Ligne sous le nom',
                'required' => false,
            ])
            ->add('chips', TextareaType::class, [
                'label' => 'Étiquettes',
                'help' => 'Une par ligne.',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'matchName' => '',
            'age' => 19,
            'hoursLeft' => 23,
            'tagline' => '📍 À 11 ans de toi · Cherche un canon event',
            'chips' => '🚩 Red flag
🎭 Canon event
🌈 Gay panic',
        ];
    }
}
