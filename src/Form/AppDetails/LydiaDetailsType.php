<?php

namespace App\Form\AppDetails;

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
                'help' => 'Qui envoie l\'aura (ou qui la réclame, si elle est négative).',
                'constraints' => [new NotBlank()],
            ])
            ->add('message', TextType::class, [
                'label' => 'Message du virement',
                'help' => 'Avec des emojis, façon « pour la pizza de 2015 🍕 ».',
                'required' => false,
            ])
            ->add('emoji', TextType::class, [
                'label' => 'Emoji de la transaction',
                'help' => 'Affiché en grand au-dessus du montant.',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'counterparty' => 'Dodo du passé',
            'message' => 'pour la pizza de 2015 🍕',
            'emoji' => '💸',
        ];
    }
}
