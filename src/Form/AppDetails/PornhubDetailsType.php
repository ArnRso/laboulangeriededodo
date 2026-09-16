<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class PornhubDetailsType extends AbstractAppDetailsType
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
                'help' => 'Vide : reprend la description.',
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
            ->add('likePercent', IntegerType::class, [
                'label' => 'Pouces en l\'air (%)',
                'help' => 'Entre 0 et 100 : la barre verte sous le titre.',
                'constraints' => [new PositiveOrZero(), new LessThanOrEqual(100)],
            ])
            ->add('duration', TextType::class, [
                'label' => 'Durée',
                'help' => 'Affichée sur le lecteur, comme « 4:12 ».',
                'required' => false,
            ])
            ->add('tags', TextareaType::class, [
                'label' => 'Catégories',
                'help' => 'Une par ligne, affichées en pastilles.',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => "lost media\ncanon event\namateur de mauvaises décisions"],
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'Commentaires',
                'help' => 'Un par ligne, sous la forme « pseudo: texte ».',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "marie83: j'étais là, je confirme\ndodo.du.passe: supprimez ça"],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'channel' => 'DodoDuPasse',
            'videoTitle' => '',
            'videoDescription' => '',
            'views' => '1,2 M de vues',
            'uploadedAgo' => '11 ans',
            'likePercent' => 98,
            'duration' => '4:12',
            'tags' => "lost media\ncanon event\namateur de mauvaises décisions",
            'comments' => '',
        ];
    }
}
