<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class DuolingoDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('streakDays', IntegerType::class, [
                'label' => 'Jours de série',
                'help' => 'Le nombre de jours « sans pratiquer » que le hibou reproche. Barré si l\'aura est négative.',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('lesson', TextType::class, [
                'label' => 'Leçon du jour',
                'constraints' => [new NotBlank()],
            ])
            ->add('course', TextType::class, [
                'label' => 'Cours suivi',
                'help' => 'Affiché comme une langue apprise, par exemple « Dodo → Adulte ».',
                'required' => false,
            ])
            ->add('owlLine', TextType::class, [
                'label' => 'Phrase du hibou',
                'help' => 'Passive-agressive, comme il se doit.',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'streakDays' => 11,
            'lesson' => 'Leçon 4 : les excuses de dernière minute',
            'course' => 'Dodo → Adulte',
            'owlLine' => 'Tu as ignoré mes rappels. Je n\'ai pas oublié. Je n\'oublie jamais.',
        ];
    }
}
