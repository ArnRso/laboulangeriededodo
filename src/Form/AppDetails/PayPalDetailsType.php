<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
                'help' => 'Qui paie.',
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
            ])
            ->add('label', TextType::class, [
                'label' => 'Libellé du paiement',
                'help' => 'Le gros titre de l\'en-tête. Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('details', TextareaType::class, [
                'label' => 'Détails',
                'help' => 'Vide : reprend la description de la notification.',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'counterparty' => 'Dodo du passé',
            'note' => 'pour le lore, merci de ne pas en parler 🤫',
            'transactionId' => '2015-CANON-EVENT-4H12',
            'fee' => 'Aucuns frais',
            'label' => '',
            'details' => '',
        ];
    }
}
