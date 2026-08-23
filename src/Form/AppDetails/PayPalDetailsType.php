<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PayPalDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('counterparty', TextType::class, [
                'label' => 'Expéditeur / destinataire',
                'help' => 'Qui paie (ou qui encaisse, si l\'aura est négative).',
                'constraints' => [new NotBlank()],
            ])
            ->add('note', TextType::class, [
                'label' => 'Petit mot du paiement',
                'help' => 'La note qui accompagne le paiement, façon « pour le lore, merci de ne pas en parler ».',
                'required' => false,
            ])
            ->add('transactionId', TextType::class, [
                'label' => 'ID de transaction',
                'required' => false,
            ])
            ->add('fee', TextType::class, [
                'label' => 'Frais affichés',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'counterparty' => 'Dodo du passé',
            'note' => 'pour le lore, merci de ne pas en parler 🤫',
            'transactionId' => '2015-CANON-EVENT-4H12',
            'fee' => 'Aucuns frais',
        ];
    }
}
