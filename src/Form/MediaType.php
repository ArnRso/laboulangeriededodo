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
    public const string MAX_FILE_SIZE = '256M';

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
            // Le type est choisi par les onglets, qui alimentent ce champ caché.
            ->add('type', EnumType::class, [
                'class' => MediaTypeEnum::class,
                'label' => false,
                'attr' => ['data-media-type-target' => 'input'],
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier',
                'required' => false,
                'help' => 'Taille maximale : '.self::MAX_FILE_SIZE.'.',
                'constraints' => [
                    new File(maxSize: self::MAX_FILE_SIZE),
                ],
            ])
            ->add('textContent', TextareaType::class, [
                'label' => 'Votre message',
                'required' => false,
                'attr' => ['rows' => 8],
            ])
            ->add('url', UrlType::class, [
                'label' => 'Adresse du lien',
                'required' => false,
                'attr' => ['placeholder' => 'https://'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}
