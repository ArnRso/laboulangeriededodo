<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * @extends AbstractType<mixed>
 */
class InstagramDetailsType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Compte qui publie',
                'constraints' => [new NotBlank()],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu / date sous le pseudo',
                'required' => false,
            ])
            ->add('likedBy', TextType::class, [
                'label' => 'Aimé par',
                'help' => 'Le compte cité en premier dans « Aimé par … et N autres ».',
                'required' => false,
            ])
            ->add('likesCount', IntegerType::class, [
                'label' => 'Nombre d\'autres likes',
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('hashtags', TextType::class, [
                'label' => 'Hashtags',
                'required' => false,
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'Commentaires',
                'help' => 'Un par ligne, sous la forme « pseudo: texte ».',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "marie: tu avais dit « je reste une heure » 💀\ndodo.du.passe: il était 4h12"],
            ])
            ->add('timeAgo', TextType::class, [
                'label' => 'Ancienneté affichée',
                'required' => false,
            ])
            ->add('badge', TextType::class, [
                'label' => 'Badge débloqué',
                'help' => 'Affiché sous le post avec l\'aura. Laisse vide pour ne rien afficher.',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
