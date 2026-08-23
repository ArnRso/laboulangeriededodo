<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MessengerDetailsType extends AbstractAppDetailsType
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
                'help' => 'Sert d\'avatar dans l\'en-tête et à côté des bulles.',
                'required' => false,
            ])
            ->add('statusLine', TextType::class, [
                'label' => 'Ligne de statut',
                'help' => 'Sous le nom : « Actif(ve) maintenant », « Actif(ve) il y a 11 ans »…',
                'required' => false,
            ])
            ->add('conversation', TextareaType::class, [
                'label' => 'Conversation',
                'help' => 'Une bulle par ligne ; commence la ligne par « moi: » pour une bulle envoyée par le destinataire, sinon elle vient du contact.',
                'required' => false,
                'attr' => ['rows' => 6, 'placeholder' => "moi: pourquoi t'as encore ça\nje garde tout. TOUT."],
            ]);
    }

    public static function defaults(): array
    {
        return [
            'contact' => 'Dodo du passé',
            'contactEmoji' => '👶',
            'statusLine' => 'Actif(ve) maintenant',
            'conversation' => "moi: pourquoi t'as encore ça
je garde tout. TOUT.
moi: supprime
non 💙",
        ];
    }
}
