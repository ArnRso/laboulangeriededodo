<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
                'help' => 'Le profil affiché : son nom coiffe l\'écran, ouvre le bandeau « … a aimé ta réponse » et signe le petit mot.',
                'constraints' => [new NotBlank()],
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Âge affiché',
                'constraints' => [new Range(min: 1, max: 150)],
            ])
            ->add('prompt', TextType::class, [
                'label' => 'Question du prompt',
                'help' => 'La petite ligne au-dessus de la réponse.',
                'required' => false,
            ])
            ->add('answer', TextareaType::class, [
                'label' => 'Réponse au prompt',
                'help' => 'Vide : reprend la description de la notification, à défaut son titre.',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('photoCaption', TextType::class, [
                'label' => 'Légende de la photo',
                'help' => 'Sous le souvenir. Vide : reprend le titre de la notification.',
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
            'answer' => '',
            'photoCaption' => '',
            'comment' => 'hear me out 👀',
        ];
    }
}
