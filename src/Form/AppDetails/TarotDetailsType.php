<?php

namespace App\Form\AppDetails;

use App\Enum\TarotCard;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Le tirage du jour : une carte du tarot de Marseille, dessinée en CSS, et
 * ce qu'elle annonce sur les quatre plans.
 */
class TarotDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('card', ChoiceType::class, [
                'label' => 'Carte du jour',
                'help' => 'Les vingt-deux arcanes majeurs, dans l\'ordre du jeu.',
                'choices' => TarotCard::choices(),
                'constraints' => [new NotBlank()],
            ])
            ->add('cardName', TextType::class, [
                'label' => 'Nom affiché sur la carte',
                'help' => 'Vide : le nom de l\'arcane choisi. Sert aussi à l\'annonce dans le fil.',
                'required' => false,
            ])
            ->add('heading', TextType::class, [
                'label' => 'Titre du tirage',
                'help' => 'Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('reading', TextareaType::class, [
                'label' => 'Description du tirage',
                'help' => 'Vide : reprend la description de la notification.',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('love', TextareaType::class, [
                'label' => '💘 Amour',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('money', TextareaType::class, [
                'label' => '💰 Argent',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('social', TextareaType::class, [
                'label' => '🎭 Social',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('danger', TextareaType::class, [
                'label' => '⚠️ Danger du jour',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Le message des cartes',
                'help' => 'Le mot de la fin, en bas de l\'écran.',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'card' => TarotCard::ARCANE_SANS_NOM->value,
            'cardName' => '',
            'heading' => '',
            'reading' => '',
            'love' => 'Quelqu\'un pense à toi. Malheureusement, c\'est ton ex.',
            'money' => 'Ton compte tient bon. Ta tournée de 2015, non.',
            'social' => 'Le groupe reparle de cette soirée. Encore.',
            'danger' => 'Ne réponds pas à ce message après 23 h.',
            'message' => 'Les cartes sont formelles : c\'était un canon event, tu n\'y pouvais rien.',
        ];
    }
}
