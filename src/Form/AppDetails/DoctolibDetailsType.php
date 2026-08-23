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
                'help' => 'Affiché avec l\'aura, par exemple « Pris en charge par la mutuelle du passé ».',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'practitioner' => 'Dr Passé',
            'specialty' => 'Spécialiste des décisions catastrophiques',
            'sector' => 'Conventionné secteur 2015',
            'address' => 'Ton adolescence
2e étage, porte du fond',
            'refundLabel' => 'Pris en charge par la mutuelle du passé',
        ];
    }
}
