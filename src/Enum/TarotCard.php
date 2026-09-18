<?php

namespace App\Enum;

/**
 * Les vingt-deux arcanes majeurs du tarot de Marseille, dans l'ordre du jeu.
 * Chaque carte est dessinée en CSS : son chiffre romain, son nom et son
 * symbole suffisent, aucune image à charger.
 */
enum TarotCard: string
{
    case MAT = 'mat';
    case BATELEUR = 'bateleur';
    case PAPESSE = 'papesse';
    case IMPERATRICE = 'imperatrice';
    case EMPEREUR = 'empereur';
    case PAPE = 'pape';
    case AMOUREUX = 'amoureux';
    case CHARIOT = 'chariot';
    case JUSTICE = 'justice';
    case ERMITE = 'ermite';
    case ROUE = 'roue';
    case FORCE = 'force';
    case PENDU = 'pendu';
    case ARCANE_SANS_NOM = 'arcane_sans_nom';
    case TEMPERANCE = 'temperance';
    case DIABLE = 'diable';
    case MAISON_DIEU = 'maison_dieu';
    case ETOILE = 'etoile';
    case LUNE = 'lune';
    case SOLEIL = 'soleil';
    case JUGEMENT = 'jugement';
    case MONDE = 'monde';

    public function label(): string
    {
        return match ($this) {
            self::MAT => 'Le Mat',
            self::BATELEUR => 'Le Bateleur',
            self::PAPESSE => 'La Papesse',
            self::IMPERATRICE => 'L\'Impératrice',
            self::EMPEREUR => 'L\'Empereur',
            self::PAPE => 'Le Pape',
            self::AMOUREUX => 'L\'Amoureux',
            self::CHARIOT => 'Le Chariot',
            self::JUSTICE => 'La Justice',
            self::ERMITE => 'L\'Ermite',
            self::ROUE => 'La Roue de Fortune',
            self::FORCE => 'La Force',
            self::PENDU => 'Le Pendu',
            self::ARCANE_SANS_NOM => 'L\'Arcane sans nom',
            self::TEMPERANCE => 'Tempérance',
            self::DIABLE => 'Le Diable',
            self::MAISON_DIEU => 'La Maison Dieu',
            self::ETOILE => 'L\'Étoile',
            self::LUNE => 'La Lune',
            self::SOLEIL => 'Le Soleil',
            self::JUGEMENT => 'Le Jugement',
            self::MONDE => 'Le Monde',
        };
    }

    /**
     * Le chiffre porté par la carte. Le Mat n'en a pas : il est hors rang.
     */
    public function numeral(): string
    {
        return match ($this) {
            self::MAT => '',
            self::BATELEUR => 'I',
            self::PAPESSE => 'II',
            self::IMPERATRICE => 'III',
            self::EMPEREUR => 'IIII',
            self::PAPE => 'V',
            self::AMOUREUX => 'VI',
            self::CHARIOT => 'VII',
            self::JUSTICE => 'VIII',
            self::ERMITE => 'VIIII',
            self::ROUE => 'X',
            self::FORCE => 'XI',
            self::PENDU => 'XII',
            self::ARCANE_SANS_NOM => 'XIII',
            self::TEMPERANCE => 'XIIII',
            self::DIABLE => 'XV',
            self::MAISON_DIEU => 'XVI',
            self::ETOILE => 'XVII',
            self::LUNE => 'XVIII',
            self::SOLEIL => 'XVIIII',
            self::JUGEMENT => 'XX',
            self::MONDE => 'XXI',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::MAT => '🎒',
            self::BATELEUR => '🎩',
            self::PAPESSE => '📖',
            self::IMPERATRICE => '👑',
            self::EMPEREUR => '🛡️',
            self::PAPE => '🕊️',
            self::AMOUREUX => '💘',
            self::CHARIOT => '🐎',
            self::JUSTICE => '⚖️',
            self::ERMITE => '🕯️',
            self::ROUE => '🎡',
            self::FORCE => '🦁',
            self::PENDU => '🙃',
            self::ARCANE_SANS_NOM => '💀',
            self::TEMPERANCE => '🏺',
            self::DIABLE => '😈',
            self::MAISON_DIEU => '🗼',
            self::ETOILE => '⭐',
            self::LUNE => '🌙',
            self::SOLEIL => '☀️',
            self::JUGEMENT => '📯',
            self::MONDE => '🌍',
        };
    }

    /**
     * Ce que la carte annonce, pour guider l'admin dans l'admin.
     */
    public function meaning(): string
    {
        return match ($this) {
            self::MAT => 'le départ sans plan',
            self::BATELEUR => 'tout commence, rien n\'est joué',
            self::PAPESSE => 'ce qu\'on ne dit pas encore',
            self::IMPERATRICE => 'l\'abondance, parfois de trop',
            self::EMPEREUR => 'le cadre, qu\'on aime ou non',
            self::PAPE => 'le conseil qu\'on n\'a pas demandé',
            self::AMOUREUX => 'le choix du cœur',
            self::CHARIOT => 'la fuite en avant, mais élégante',
            self::JUSTICE => 'le retour de bâton',
            self::ERMITE => 'le mode avion émotionnel',
            self::ROUE => 'ça tourne, tiens-toi',
            self::FORCE => 'tenir bon sans crier',
            self::PENDU => 'l\'attente forcée',
            self::ARCANE_SANS_NOM => 'la fin qui libère',
            self::TEMPERANCE => 'le calme après la tempête',
            self::DIABLE => 'la mauvaise idée qui plaît',
            self::MAISON_DIEU => 'tout s\'écroule, enfin',
            self::ETOILE => 'l\'espoir qui revient',
            self::LUNE => 'le flou et les illusions',
            self::SOLEIL => 'la joie franche',
            self::JUGEMENT => 'le réveil, le vrai',
            self::MONDE => 'la boucle bouclée',
        };
    }

    /**
     * Les choix du formulaire : « XIII · L'Arcane sans nom » => 'arcane_sans_nom'.
     *
     * @return array<string, string>
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $card) {
            $numeral = '' !== $card->numeral() ? $card->numeral().' · ' : '';
            $choices[sprintf('%s%s %s', $numeral, $card->label(), $card->symbol())] = $card->value;
        }

        return $choices;
    }
}
