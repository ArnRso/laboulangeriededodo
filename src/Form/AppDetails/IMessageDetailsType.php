<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class IMessageDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contact', TextType::class, [
                'label' => 'Contact',
                'help' => 'Qui envoie le souvenir — une personne, un groupe, un ex.',
                'constraints' => [new NotBlank()],
            ])
            ->add('contactEmoji', TextType::class, [
                'label' => 'Emoji du contact',
                'help' => 'Sert d\'avatar dans l\'en-tête.',
                'required' => false,
            ])
            ->add('statusLine', TextType::class, [
                'label' => 'Ligne de date',
                'help' => 'Centrée au-dessus de la conversation : « Il y a 11 ans, 23:41 »…',
                'required' => false,
            ])
            ->add('conversation', TextareaType::class, [
                'label' => 'Conversation',
                'help' => 'Une bulle par ligne ; commence la ligne par « moi: » pour une bulle envoyée par le destinataire, sinon elle vient du contact.',
                'required' => false,
                'attr' => ['rows' => 6, 'placeholder' => "moi: explique\ntu avais dit « je reste une heure »"],
            ])
            ->add('deliveredLabel', TextType::class, [
                'label' => 'Accusé sous la dernière bulle envoyée',
                'help' => '« Distribué », « Lu il y a 11 ans »…',
                'required' => false,
            ])
            ->add('mediaCaption', TextType::class, [
                'label' => 'Légende sous le souvenir',
                'help' => 'Dans la bulle qui porte le souvenir. Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('lastMessage', TextType::class, [
                'label' => 'Dernier message du contact',
                'help' => 'La bulle qui ferme la conversation. Vide : reprend la description de la notification.',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'contact' => 'Dodo du passé',
            'contactEmoji' => '👶',
            'statusLine' => 'Il y a 11 ans, 23:41',
            'conversation' => 'moi: explique
tu avais dit « je reste une heure »
moi: et ?
il était 4h12.',
            'deliveredLabel' => 'Lu il y a 11 ans',
            'mediaCaption' => '',
            'lastMessage' => '',
        ];
    }
}
