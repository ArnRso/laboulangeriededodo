<?php

namespace App\Form\AppDetails;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

class QuizDetailsType extends AbstractAppDetailsType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quizName', TextType::class, [
                'label' => 'Nom du quiz',
                'help' => 'Annoncé dans le fil et affiché en haut de l\'écran.',
                'constraints' => [new NotBlank()],
            ])
            ->add('questionNumber', IntegerType::class, [
                'label' => 'Numéro de la question',
                'help' => 'Le premier chiffre de « Question 4 / 10 ».',
                'constraints' => [new Positive()],
            ])
            ->add('questionTotal', IntegerType::class, [
                'label' => 'Nombre total de questions',
                'help' => 'Le second chiffre, qui remplit aussi la barre de progression.',
                'constraints' => [new Positive()],
            ])
            ->add('question', TextType::class, [
                'label' => 'Intitulé de la question',
                'help' => 'Vide : reprend le titre de la notification.',
                'required' => false,
            ])
            ->add('answers', TextareaType::class, [
                'label' => 'Réponses possibles',
                'help' => 'Une par ligne. Préfixe une ligne par * pour marquer la bonne réponse.',
                'required' => false,
                'attr' => ['rows' => 5, 'placeholder' => "à l'arrache, comme toujours\n*en mode canon event\nen niant tout en bloc\nje préfère ne pas répondre"],
            ])
            ->add('resultTitle', TextType::class, [
                'label' => 'Titre du résultat',
                'help' => 'Le verdict, au-dessus du score.',
                'required' => false,
            ])
            ->add('resultText', TextareaType::class, [
                'label' => 'Texte du résultat',
                'help' => 'Vide : reprend la description de la notification.',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('score', IntegerType::class, [
                'label' => 'Score (%)',
                'help' => 'Affiché en gros et dans la barre du résultat.',
                'constraints' => [new Range(min: 0, max: 100)],
            ])
            ->add('badge', TextType::class, [
                'label' => 'Badge débloqué',
                'help' => 'Optionnel : vide, l\'encart disparaît.',
                'required' => false,
            ]);
    }

    public static function defaults(): array
    {
        return [
            'quizName' => 'À quel point tu connais ton passé ?',
            'questionNumber' => 4,
            'questionTotal' => 10,
            'question' => '',
            'answers' => "à l'arrache, comme toujours\n*en mode canon event\nen niant tout en bloc\nje préfère ne pas répondre",
            'resultTitle' => 'Tu es à 87 % un canon event',
            'resultText' => '',
            'score' => 87,
            'badge' => '🏆 Lore unlocked : tu étais là, et tu assumes',
        ];
    }
}
