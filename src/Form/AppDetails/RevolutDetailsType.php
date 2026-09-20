<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class RevolutDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('counterparty', TextType::class, [
                'label' => 'Bénéficiaire / expéditeur',
                'help' => 'Le nom en face de la transaction — une personne, un lieu, une décision.',
                'constraints' => [new NotBlank()],
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'help' => 'Le libellé bancaire, par exemple « CB 2015 SOIRÉE DONT ON NE PARLE PLUS ».',
                'required' => false,
            ])
            ->add('cardLast4', TextType::class, [
                'label' => 'Quatre derniers chiffres de la carte',
                'required' => false,
                'attr' => ['maxlength' => 4, 'inputmode' => 'numeric'],
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'help' => 'Par exemple « Décisions », « Nostalgie », « Sorties ».',
                'required' => false,
            ])
            ->add('statusLabel', TextType::class, [
                'label' => 'Statut affiché',
                'help' => 'Dans la ligne « Statut » de la fiche.',
                'required' => false,
            ])
            ->add('heroStatus', TextType::class, [
                'label' => 'Statut à côté du nom',
                'help' => 'Dans l\'en-tête, après le nom. Vide : reprend le statut affiché.',
                'required' => false,
            ])
            ->add('label', TextType::class, [
                'label' => 'Libellé de la transaction',
                'help' => 'Le gros titre de l\'en-tête. Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note',
                'help' => 'Vide : reprend la description de la notification.',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'counterparty' => 'le.pot.agé',
            'reference' => 'CB 2015 SOIRÉE DONT ON NE PARLE PLUS',
            'cardLast4' => '2015',
            'category' => 'Décisions',
            'statusLabel' => 'Terminé',
            'heroStatus' => '',
            'label' => '',
            'note' => '',
        ];
    }
}
