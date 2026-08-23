<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class SpotifyDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('artist', TextType::class, [
                'label' => 'Artiste',
                'help' => 'Le titre de la notification est le nom du morceau.',
                'constraints' => [new NotBlank()],
            ])
            ->add('album', TextType::class, [
                'label' => 'Album',
                'required' => false,
            ])
            ->add('playlist', TextType::class, [
                'label' => 'Playlist',
                'help' => 'Affichée en haut : « Lecture depuis la playlist … ».',
                'required' => false,
            ])
            ->add('duration', TextType::class, [
                'label' => 'Durée',
                'help' => 'Sous la forme « 3:47 ».',
                'required' => false,
            ])
            ->add('plays', TextType::class, [
                'label' => 'Écoutes',
                'help' => 'Texte libre, par exemple « 1 240 écoutes ».',
                'required' => false,
            ])
            ->add('progress', IntegerType::class, [
                'label' => 'Position de la barre (%)',
                'constraints' => [new Range(min: 0, max: 100)],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'artist' => 'Dodo du passé',
            'album' => 'Lost Media (Deluxe)',
            'playlist' => 'Tes années lycée',
            'duration' => '3:47',
            'plays' => '1 240 écoutes',
            'progress' => 42,
        ];
    }
}
