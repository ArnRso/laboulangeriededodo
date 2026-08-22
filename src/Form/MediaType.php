<?php

namespace App\Form;

use App\Entity\Media;
use App\Enum\AppKind;
use App\Enum\MediaType as MediaTypeEnum;
use App\Form\AppDetails\AppDetailsRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * Une notification du fil. L'application imitée est fixée en amont : elle
 * décide du sous-formulaire de détails.
 *
 * @extends AbstractType<Media>
 */
class MediaType extends AbstractType
{
    public const string MAX_FILE_SIZE = '256M';

    public function __construct(
        private readonly AppDetailsRegistry $registry,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $appKind = $options['app_kind'];
        \assert($appKind instanceof AppKind);

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
                'help' => 'Reprise par l\'app à sa façon : note du restaurant, légende, bio du match, compte-rendu…',
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
            ])
            ->add('delayMinutes', DelayType::class, [
                'label' => 'Délai d\'arrivée',
                'allow_zero' => true,
                'help' => 'Temps après l\'ouverture de la notification précédente. Zéro pour enchaîner tout de suite ; sans effet sur la première du fil.',
            ])
            ->add('auraPoints', IntegerType::class, [
                'label' => 'Aura gagnée à l\'ouverture',
                'help' => 'Positif par défaut. Mets une valeur négative pour une décision objectivement catastrophique.',
            ])
            ->add('auraMessage', TextareaType::class, [
                'label' => 'Phrase d\'aura',
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => 'Tu viens de débloquer +100 aura. Ne la gaspille pas.'],
                'help' => 'Affichée avec le gain d\'aura. Laisse vide pour la phrase par défaut.',
            ])
            ->add('published', CheckboxType::class, [
                'label' => 'Dans le fil',
                'required' => false,
                'help' => 'Décoché, la notification reste un brouillon que le destinataire ne voit pas.',
            ])
            ->add('appData', $this->registry->formTypeFor($appKind), [
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['data_class' => Media::class])
            ->setRequired('app_kind')
            ->setAllowedTypes('app_kind', AppKind::class);
    }
}
