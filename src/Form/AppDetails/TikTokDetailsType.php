<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class TikTokDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Compte qui publie',
                'help' => 'Sans le @, il est ajouté à l\'écran.',
                'constraints' => [new NotBlank()],
            ])
            ->add('sound', TextType::class, [
                'label' => 'Son original',
                'help' => 'Le bandeau qui défile en bas de la vidéo.',
                'required' => false,
            ])
            ->add('likes', IntegerType::class, [
                'label' => 'Likes',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('comments', IntegerType::class, [
                'label' => 'Commentaires',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('shares', IntegerType::class, [
                'label' => 'Partages',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('hashtags', TextType::class, [
                'label' => 'Hashtags',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'username' => 'dodo.du.passe',
            'sound' => 'son original – dodo.du.passe · lore unlocked (sped up)',
            'likes' => 48200,
            'comments' => 1312,
            'shares' => 2015,
            'hashtags' => '#pov #canonevent #lostmedia #fyp',
        ];
    }
}
