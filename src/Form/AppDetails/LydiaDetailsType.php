<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class LydiaDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('counterparty', TextType::class, [
                'label' => 'Ami qui envoie / reçoit',
                'help' => 'Qui envoie le virement.',
                'constraints' => [new NotBlank()],
            ])
            ->add('message', TextType::class, [
                'label' => 'Message du virement',
                'help' => 'Avec des emojis, façon « pour la pizza de 2015 🍕 ».',
                'required' => false,
            ])
            ->add('emoji', TextType::class, [
                'label' => 'Emoji de la transaction',
                'help' => 'Affiché en grand au-dessus du libellé.',
                'required' => false,
            ])
            ->add('label', TextType::class, [
                'label' => 'Libellé du virement',
                'help' => 'Le gros titre de la carte. Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire sous la photo',
                'help' => 'Vide : reprend la description de la notification.',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('commentAuthor', TextType::class, [
                'label' => 'Auteur du commentaire',
                'help' => 'Vide : reprend l\'ami qui envoie.',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'counterparty' => 'le.pot.agé',
            'message' => 'pour la pizza de 2015 🍕',
            'emoji' => '💸',
            'label' => '',
            'comment' => '',
            'commentAuthor' => '',
        ];
    }
}
