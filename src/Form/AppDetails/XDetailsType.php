<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class XDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayName', TextType::class, [
                'label' => 'Nom affiché',
                'constraints' => [new NotBlank()],
            ])
            ->add('handle', TextType::class, [
                'label' => 'Identifiant',
                'help' => 'Sans le @, il est ajouté à l\'écran.',
                'required' => false,
            ])
            ->add('verified', CheckboxType::class, [
                'label' => 'Badge bleu certifié',
                'required' => false,
            ])
            ->add('postText', TextareaType::class, [
                'label' => 'Texte du post',
                'help' => 'Vide : reprend le titre.',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('postExtra', TextareaType::class, [
                'label' => 'Second paragraphe du post',
                'help' => 'Vide : reprend la description.',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('replies', IntegerType::class, [
                'label' => 'Réponses',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('reposts', IntegerType::class, [
                'label' => 'Reposts',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('likes', IntegerType::class, [
                'label' => 'J\'aime',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('views', IntegerType::class, [
                'label' => 'Vues',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('postedAgo', TextType::class, [
                'label' => 'Ancienneté affichée',
                'help' => 'Après le @, comme « 11 ans ».',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'displayName' => 'le.pot.agé',
            'handle' => 'dodo.du.passe',
            'verified' => true,
            'postText' => '',
            'postExtra' => '',
            'replies' => 412,
            'reposts' => 2015,
            'likes' => 13400,
            'views' => 1200000,
            'postedAgo' => '11 ans',
        ];
    }
}
