<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CalendarDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', TextType::class, [
                'label' => 'Date',
                'help' => 'En toutes lettres, telle qu\'affichée : « Samedi 23 août 2015 ».',
                'constraints' => [new NotBlank()],
            ])
            ->add('timeRange', TextType::class, [
                'label' => 'Horaire',
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('attendees', TextareaType::class, [
                'label' => 'Participants',
                'help' => 'Un par ligne. Les initiales servent d\'avatar.',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "Dodo du passé\nTa dignité (a décliné)"],
            ])
            ->add('alert', TextType::class, [
                'label' => 'Alerte',
                'required' => false,
            ])
            ->add('calendarName', TextType::class, [
                'label' => 'Nom du calendrier',
                'required' => false,
            ])
            ->add('eventTitle', TextType::class, [
                'label' => 'Titre de l\'événement',
                'help' => 'Le gros titre en haut de la fiche. Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'help' => 'Vide : reprend la description de la notification.',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'date' => 'Samedi 23 août 2015',
            'timeRange' => '21:00 – 04:12',
            'location' => 'Chez quelqu\'un dont tu as oublié le nom',
            'attendees' => 'Dodo du passé
Ta dignité (a décliné)
Le drama (a accepté)',
            'alert' => 'Il y a 11 ans',
            'calendarName' => 'Canon events',
            'eventTitle' => '',
            'notes' => '',
        ];
    }
}
