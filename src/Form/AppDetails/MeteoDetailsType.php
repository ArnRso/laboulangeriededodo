<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class MeteoDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'help' => 'Le lieu du bulletin — une ville, une époque, un état d\'esprit.',
                'constraints' => [new NotBlank()],
            ])
            ->add('temperature', IntegerType::class, [
                'label' => 'Température (°)',
            ])
            ->add('condition', TextType::class, [
                'label' => 'Conditions',
                'help' => 'Le mot soleil, pluie, orage, neige ou nuage choisit le gros emoji.',
                'constraints' => [new NotBlank()],
            ])
            ->add('high', IntegerType::class, [
                'label' => 'Maximale (°)',
            ])
            ->add('low', IntegerType::class, [
                'label' => 'Minimale (°)',
            ])
            ->add('hourly', TextareaType::class, [
                'label' => 'Prévisions heure par heure',
                'help' => 'Une par ligne, sous la forme « 14h ☀️ 22° ».',
                'required' => false,
                'attr' => ['rows' => 5, 'placeholder' => "21h 🍹 24°\n23h 🎤 27°\n01h 🌪️ 31°\n04h 💀 12°"],
            ])
            ->add('dramaIndex', IntegerType::class, [
                'label' => 'Indice drama (%)',
                'constraints' => [new Range(min: 0, max: 100)],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'city' => 'Ton adolescence',
            'temperature' => 23,
            'condition' => 'Nuageux avec risque de drama',
            'high' => 31,
            'low' => 12,
            'hourly' => '21h 🍹 24°
23h 🎤 27°
01h 🌪️ 31°
04h 💀 12°',
            'dramaIndex' => 87,
        ];
    }
}
