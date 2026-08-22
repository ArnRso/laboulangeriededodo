<?php

namespace App\Form;

use App\Enum\Avatar;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<null>
 */
class InviteRecipientType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email du destinataire',
                'constraints' => [
                    new NotBlank(message: 'Merci de saisir une adresse email.'),
                    new Email(message: 'Cette adresse email n\'est pas valide.'),
                ],
            ])
            ->add('displayName', TextType::class, [
                'label' => 'Prénom ou surnom',
                'help' => 'C\'est ce nom qui s\'affichera dans son espace.',
                'constraints' => [
                    new NotBlank(message: 'Merci de saisir un prénom.'),
                    new Length(max: 60),
                ],
            ])
            ->add('avatar', EnumType::class, [
                'label' => 'Avatar',
                'class' => Avatar::class,
                'choice_label' => static fn (Avatar $avatar): string => $avatar->value.'  '.$avatar->label(),
                'expanded' => true,
                'constraints' => [
                    new NotBlank(message: 'Merci de choisir un avatar.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
