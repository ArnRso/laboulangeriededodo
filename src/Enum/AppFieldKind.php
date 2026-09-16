<?php

namespace App\Enum;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormTypeInterface;

/**
 * La nature d'un champ de détails d'une app, telle que l'habillage doit la
 * comprendre pour y verser un texte : les cinq seuls types que les
 * formulaires de détails utilisent.
 */
enum AppFieldKind: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case INTEGER = 'integer';
    case CHECKBOX = 'checkbox';
    case CHOICE = 'choice';

    /**
     * @param FormTypeInterface<mixed> $type
     */
    public static function fromFormType(FormTypeInterface $type): self
    {
        return match (true) {
            $type instanceof TextareaType => self::TEXTAREA,
            $type instanceof IntegerType => self::INTEGER,
            $type instanceof CheckboxType => self::CHECKBOX,
            $type instanceof ChoiceType => self::CHOICE,
            default => self::TEXT,
        };
    }

    /**
     * Un champ sur plusieurs lignes peut recevoir plusieurs sources, mises
     * bout à bout : des commentaires, des articles, des réponses.
     */
    public function acceptsSeveralSources(): bool
    {
        return self::TEXTAREA === $this;
    }
}
