<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class WazeDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'help' => 'Le lieu cité dans l\'accroche « Itinéraire vers … ».',
                'constraints' => [new NotBlank()],
            ])
            ->add('eta', TextType::class, [
                'label' => 'Temps d\'arrivée',
                'required' => false,
            ])
            ->add('distance', TextType::class, [
                'label' => 'Distance',
                'required' => false,
            ])
            ->add('alerts', TextareaType::class, [
                'label' => 'Alertes sur la route',
                'help' => 'Une par ligne, emoji compris.',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "🚓 Radar de nostalgie dans 300 m\n🚧 Travaux sur ta maturité"],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'destination' => 'ton adolescence',
            'eta' => '4 min',
            'distance' => '11 ans',
            'alerts' => '🚓 Radar de nostalgie dans 300 m
🚧 Travaux sur ta maturité',
        ];
    }
}
