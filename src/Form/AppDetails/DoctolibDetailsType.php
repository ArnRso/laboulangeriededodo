<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DoctolibDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('intro', TextType::class, [
                'label' => 'Phrase sous « Rendez-vous honoré »',
                'help' => 'Vide : la ligne disparaît.',
                'required' => false,
            ])
            ->add('practitioner', TextType::class, [
                'label' => 'Praticien',
                'constraints' => [new NotBlank()],
            ])
            ->add('specialty', TextType::class, [
                'label' => 'Spécialité',
                'required' => false,
            ])
            ->add('sector', TextType::class, [
                'label' => 'Conventionnement',
                'required' => false,
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse du cabinet',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('refundLabel', TextType::class, [
                'label' => 'Libellé du remboursement',
                'help' => 'Par exemple « Pris en charge par la mutuelle du passé ».',
                'required' => false,
            ])
            ->add('reason', TextType::class, [
                'label' => 'Motif de consultation',
                'help' => 'Dans la fiche du rendez-vous. Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('report', TextareaType::class, [
                'label' => 'Compte-rendu',
                'help' => 'Vide : reprend la description de la notification.',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'intro' => 'Canon event · Cette journée était inévitable. Tu as accepté ton destin.',
            'practitioner' => 'Dr Passé',
            'specialty' => 'Spécialiste des décisions catastrophiques',
            'sector' => 'Conventionné secteur 2015',
            'address' => 'Ton adolescence
2e étage, porte du fond',
            'refundLabel' => 'Pris en charge par la mutuelle du passé',
            'reason' => '',
            'report' => '',
        ];
    }
}
