<?php

namespace App\Form;

use App\Entity\Media;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Les étiquettes de rangement, saisies d'un trait et séparées par des
 * virgules : plus court à taper qu'une ligne par étiquette, et le tri se fait
 * sur des mots courts.
 *
 * @extends AbstractType<list<string>>
 */
class TagsType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            /**
             * @param list<string>|null $tags
             */
            static function (?array $tags): string {
                return implode(', ', array_filter($tags ?? [], is_string(...)));
            },
            /**
             * La normalisation complète appartient au média : ici on se
             * contente de découper, lui écarte les vides et les doublons.
             *
             * @return list<string>
             */
            static function (?string $raw): array {
                return array_values(array_filter(
                    array_map(trim(...), explode(',', $raw ?? '')),
                    static fn (string $tag): bool => '' !== $tag,
                ));
            },
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'empty_data' => '',
            'attr' => [
                'placeholder' => 'souvenirs, voyage, à retravailler',
                'maxlength' => 255,
            ],
            'help' => sprintf(
                'Séparées par des virgules, %d caractères au plus chacune. Pour ton rangement : le destinataire ne les voit jamais.',
                Media::MAX_TAG_LENGTH,
            ),
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
