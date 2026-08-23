<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class HingeDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du profil',
                'help' => 'Celui ou celle qui a aimé la réponse, et dont le profil s\'affiche.',
                'constraints' => [new NotBlank()],
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Âge affiché',
                'constraints' => [new Range(min: 1, max: 150)],
            ])
            ->add('prompt', TextType::class, [
                'label' => 'Question du prompt',
                'help' => 'La description de la notification devient la réponse à ce prompt.',
                'required' => false,
            ])
            ->add('likedBy', TextType::class, [
                'label' => 'Qui a aimé la réponse',
                'help' => 'Affiché dans le bandeau « … a aimé ta réponse ». Vide : le nom du profil.',
                'required' => false,
            ])
            ->add('comment', TextType::class, [
                'label' => 'Petit mot laissé avec le like',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'name' => '',
            'age' => 19,
            'prompt' => 'Ce qui me rend heureux',
            'likedBy' => '',
            'comment' => 'hear me out 👀',
        ];
    }
}
