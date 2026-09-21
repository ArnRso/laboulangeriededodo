<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Range;

class NetflixDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('match', IntegerType::class, [
                'label' => 'Correspondance (%)',
                'constraints' => [new Range(min: 0, max: 100)],
            ])
            ->add('showTitle', TextType::class, [
                'label' => 'Titre de la fiche',
                'help' => 'Vide : reprend le titre.',
                'required' => false,
            ])
            ->add('synopsis', TextareaType::class, [
                'label' => 'Synopsis',
                'help' => 'Vide : reprend la description.',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('year', TextType::class, [
                'label' => 'Année',
                'required' => false,
            ])
            ->add('rating', TextType::class, [
                'label' => 'Classification',
                'help' => 'Par exemple « 16+ » ou « Tous publics ».',
                'required' => false,
            ])
            ->add('duration', TextType::class, [
                'label' => 'Durée',
                'help' => 'Par exemple « 1 h 47 ».',
                'required' => false,
            ])
            ->add('genres', TextType::class, [
                'label' => 'Genres',
                'help' => 'Séparés par « · », par exemple « Drame · Comédie · Documentaire ».',
                'required' => false,
            ])
            ->add('topTen', CheckboxType::class, [
                'label' => 'Badge « Top 10 · N° 1 aujourd\'hui »',
                'required' => false,
            ])
            ->add('cast', TextType::class, [
                'label' => 'Distribution',
                'help' => 'Affichée après « Avec : ».',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'match' => 98,
            'showTitle' => '',
            'synopsis' => '',
            'year' => '2015',
            'rating' => '16+',
            'duration' => '1 h 47',
            'genres' => 'Drame · Comédie · Documentaire',
            'topTen' => true,
            'cast' => 'le.pot.agé, ta mère, le groupe WhatsApp',
        ];
    }
}
