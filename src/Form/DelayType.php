<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Saisie d'une durée en heures et minutes, stockée en minutes.
 *
 * @extends AbstractType<int>
 */
class DelayType extends AbstractType
{
    /**
     * Un mois de délai : au-delà, la valeur relève plus de la faute de frappe
     * que d'une intention.
     */
    public const int MAX_HOURS = 720;

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hours', IntegerType::class, [
                'label' => 'Heures',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => self::MAX_HOURS,
                    'step' => 1,
                    'placeholder' => '0',
                    'inputmode' => 'numeric',
                ],
            ])
            ->add('minutes', IntegerType::class, [
                'label' => 'Minutes',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => 59,
                    'step' => 1,
                    'placeholder' => '0',
                    'inputmode' => 'numeric',
                ],
            ]);

        $builder->addModelTransformer(new CallbackTransformer(
            /**
             * @return array{hours: int, minutes: int}
             */
            static function (?int $totalMinutes): array {
                $totalMinutes ??= 0;

                return [
                    'hours' => intdiv($totalMinutes, 60),
                    'minutes' => $totalMinutes % 60,
                ];
            },
            /**
             * @param array<string, mixed> $parts
             */
            static function (array $parts): int {
                $hours = \is_int($parts['hours'] ?? null) ? $parts['hours'] : 0;
                $minutes = \is_int($parts['minutes'] ?? null) ? $parts['minutes'] : 0;

                return $hours * 60 + $minutes;
            },
        ));

        $allowZero = $options['allow_zero'];
        \assert(\is_bool($allowZero));

        // Les bornes sont vérifiées sur les champs saisis pour que le message
        // se rattache à celui qui est fautif plutôt qu'au total.
        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) use ($allowZero): void {
            $form = $event->getForm();
            $rawHours = $form->get('hours')->getData();
            $rawMinutes = $form->get('minutes')->getData();

            $hours = \is_int($rawHours) ? $rawHours : 0;
            $minutes = \is_int($rawMinutes) ? $rawMinutes : 0;

            if ($minutes < 0 || $minutes > 59) {
                $form->get('minutes')->addError(new FormError('Les minutes doivent être comprises entre 0 et 59.'));

                return;
            }

            if ($hours < 0 || $hours > self::MAX_HOURS) {
                $form->get('hours')->addError(new FormError(sprintf(
                    'Le nombre d\'heures doit être compris entre 0 et %d.',
                    self::MAX_HOURS,
                )));

                return;
            }

            if (!$allowZero && 0 === $hours * 60 + $minutes) {
                $form->addError(new FormError('Le délai doit être d\'au moins une minute.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'error_bubbling' => false,
                'allow_zero' => false,
            ])
            ->setAllowedTypes('allow_zero', 'bool');
    }
}
