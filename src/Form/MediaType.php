<?php

namespace App\Form;

use App\Entity\Media;
use App\Enum\MediaType as MediaTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * @extends AbstractType<Media>
 */
class MediaType extends AbstractType
{
    public const MAX_FILE_SIZE = '256M';

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('type', EnumType::class, [
                'label' => 'Type de média',
                'class' => MediaTypeEnum::class,
                'choice_label' => static fn (MediaTypeEnum $type): string => $type->label(),
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier',
                'required' => false,
                'help' => 'Pour les photos, vidéos et audio. Taille maximale : '.self::MAX_FILE_SIZE.'.',
                'constraints' => [
                    new File(maxSize: self::MAX_FILE_SIZE),
                ],
            ])
            ->add('textContent', TextareaType::class, [
                'label' => 'Texte',
                'required' => false,
                'attr' => ['rows' => 6],
                'help' => 'Pour les médias de type Texte.',
            ])
            ->add('url', UrlType::class, [
                'label' => 'Lien',
                'required' => false,
                'help' => 'Pour les médias de type Lien.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}
