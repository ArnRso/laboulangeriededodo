<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class BeRealDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Compte qui poste',
                'constraints' => [new NotBlank()],
            ])
            ->add('lateBy', TextType::class, [
                'label' => 'Retard affiché',
                'help' => 'Après « en retard de », comme « 11 ans ».',
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('realmojis', TextareaType::class, [
                'label' => 'RealMojis',
                'help' => 'Un par ligne, sous la forme « emoji pseudo ».',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "😂 marie\n💀 ta.mere"],
            ])
            ->add('retakes', IntegerType::class, [
                'label' => 'Nombre de retakes',
                'constraints' => [new PositiveOrZero()],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'username' => 'dodo.du.passe',
            'lateBy' => '11 ans',
            'location' => 'Quelque part dans ton adolescence',
            'realmojis' => "💀 ta.mere\n😭 marie\n👀 un.ex",
            'retakes' => 14,
        ];
    }
}
