<?php

namespace App\Form\AppDetails;

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
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'counterparty' => 'Dodo du passé',
            'reference' => 'CB 2015 SOIRÉE DONT ON NE PARLE PLUS',
            'cardLast4' => '2015',
            'category' => 'Décisions',
            'statusLabel' => 'Terminé',
        ];
    }
}
