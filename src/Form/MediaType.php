<?php

namespace App\Form;

use App\Entity\Media;
use App\Enum\AppKind;
use App\Enum\MediaType as MediaTypeEnum;
use App\Form\AppDetails\AppDetailsRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * Une notification du fil. L'application imitée est fixée en amont, ou pas
 * encore : un brouillon n'a ni détails d'app ni case « Dans le fil », mais
 * déjà ses fragments de texte à placer plus tard, et ses étiquettes de
 * rangement.
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
        \assert(null === $appKind || $appKind instanceof AppKind);

        $builder
            // Les setters du média sont typés : un champ vidé doit arriver
            // comme une chaîne vide ou un zéro, que la validation refusera ou
            // non, plutôt que comme un null qui ferait planter l'affectation.
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'empty_data' => '',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
                'help' => 'Reprise dans le fil, sous le titre.',
            ])
            // Le type est choisi par les onglets, qui alimentent ce champ caché.
            // Absent de la requête, il retombe sur le texte : le setter est
            // typé et n'accepterait pas le null d'un formulaire tronqué.
            ->add('type', EnumType::class, [
                'class' => MediaTypeEnum::class,
                'label' => false,
                'empty_data' => MediaTypeEnum::TEXT->value,
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
            ->add('fragments', CollectionType::class, [
                'entry_type' => FragmentType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('tags', TagsType::class, [
                'label' => 'Étiquettes',
            ])
            ->add('delayMinutes', DelayType::class, [
                'label' => 'Délai d\'arrivée',
                'allow_zero' => true,
                'help' => 'Temps après l\'ouverture de la notification précédente. Zéro pour enchaîner tout de suite ; sans effet sur la première du fil.',
            ]);

        if (null === $appKind) {
            return;
        }

        $builder
            ->add('published', CheckboxType::class, [
                'label' => 'Dans le fil',
                'required' => false,
                'help' => 'Décoché, la notification reste hors du fil : le destinataire ne la voit pas.',
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
            ->setAllowedTypes('app_kind', ['null', AppKind::class]);
    }
}
