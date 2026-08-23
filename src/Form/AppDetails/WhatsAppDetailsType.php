<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class WhatsAppDetailsType extends AbstractAppDetailsType
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
                'label' => 'Ligne de statut',
                'help' => 'Sous le nom : « en ligne », « vu il y a 11 ans »…',
                'required' => false,
            ])
            ->add('conversation', TextareaType::class, [
                'label' => 'Conversation',
                'help' => 'Une bulle par ligne ; commence la ligne par « moi: » pour une bulle envoyée par le destinataire, sinon elle vient du contact.',
                'required' => false,
                'attr' => ['rows' => 6, 'placeholder' => "moi: c'est qui ???\nc'est toi. en 2014. lore unlocked 🧩"],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'contact' => 'Dodo du passé',
            'contactEmoji' => '👶',
            'statusLine' => 'vu il y a 11 ans',
            'conversation' => "moi: c'est qui ???
c'est toi. en 2014. lore unlocked 🧩
moi: je vais vomir
normal, c'était un canon event 🔒",
        ];
    }
}
