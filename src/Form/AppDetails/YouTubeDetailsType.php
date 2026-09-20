<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class YouTubeDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('channel', TextType::class, [
                'label' => 'Chaîne',
                'constraints' => [new NotBlank()],
            ])
            ->add('videoTitle', TextType::class, [
                'label' => 'Titre sous le lecteur',
                'help' => 'Vide : reprend le titre.',
                'required' => false,
            ])
            ->add('videoDescription', TextareaType::class, [
                'label' => 'Description sous le lecteur',
                'help' => 'Le texte du panneau « … plus ». Vide : reprend la description.',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('views', TextType::class, [
                'label' => 'Vues affichées',
                'help' => 'Texte libre, comme « 1,2 M de vues ».',
                'required' => false,
            ])
            ->add('uploadedAgo', TextType::class, [
                'label' => 'Mise en ligne',
                'help' => 'Après « il y a », comme « 11 ans ».',
                'required' => false,
            ])
            ->add('likes', IntegerType::class, [
                'label' => 'Likes',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('subscribers', TextType::class, [
                'label' => 'Abonnés affichés',
                'help' => 'Texte libre, comme « 42 k abonnés ».',
                'required' => false,
            ])
            ->add('duration', TextType::class, [
                'label' => 'Durée',
                'help' => 'Affichée dans le lecteur, comme « 4:12 ».',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'channel' => 'le.pot.agé',
            'videoTitle' => '',
            'videoDescription' => '',
            'views' => '1,2 M de vues',
            'uploadedAgo' => '11 ans',
            'likes' => 42000,
            'subscribers' => '42 k abonnés',
            'duration' => '4:12',
        ];
    }
}
