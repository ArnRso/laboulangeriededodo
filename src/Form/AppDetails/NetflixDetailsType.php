<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            ->add('year', TextType::class, [
                'label' => 'Année',
                'required' => false,
            ])
            ->add('rating', TextType::class, [
                'label' => 'Classification',
                'help' => 'Par exemple « 16+ » ou « Tous publics ».',
                'required' => false,
            ])
            ->add('seasons', TextType::class, [
                'label' => 'Saisons ou durée',
                'help' => '« 1 saison », « 2 h 12 »…',
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
            'year' => '2015',
            'rating' => '16+',
            'seasons' => '1 saison',
            'genres' => 'Drame · Comédie · Documentaire',
            'topTen' => true,
            'cast' => 'Dodo du passé, ta mère, le groupe WhatsApp',
        ];
    }
}
