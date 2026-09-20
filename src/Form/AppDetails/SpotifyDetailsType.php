<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
                'help' => 'Affiché sous le titre du morceau et dans la carte « À propos de l\'artiste ».',
                'constraints' => [new NotBlank()],
            ])
            ->add('trackTitle', TextType::class, [
                'label' => 'Titre du morceau sous la pochette',
                'help' => 'Vide : reprend le titre.',
                'required' => false,
            ])
            ->add('lyrics', TextareaType::class, [
                'label' => 'Paroles',
                'help' => 'Le texte de la carte Paroles. Vide : reprend la description.',
                'required' => false,
                'attr' => ['rows' => 4],
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
            'artist' => 'le.pot.agé',
            'trackTitle' => '',
            'lyrics' => '',
            'album' => 'Lost Media (Deluxe)',
            'playlist' => 'Tes années lycée',
            'duration' => '3:47',
            'plays' => '1 240 écoutes',
            'progress' => 42,
        ];
    }
}
