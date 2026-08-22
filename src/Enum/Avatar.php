<?php

namespace App\Enum;

/**
 * Avatars proposés au destinataire, choisis par l'administration à l'invitation.
 */
enum Avatar: string
{
    case DODO = '🦤';
    case RACECAR = '🏎️';
    case CROWN = '👑';
    case UNICORN = '🦄';
    case RAINBOW = '🌈';
    case PEACH = '🍑';
    case NAILS = '💅';
    case DRAGON = '🐉';
    case ROLLER = '🛼';
    case MASKS = '🎭';
    case JUICE = '🧃';
    case FIRE = '🔥';

    public function label(): string
    {
        return match ($this) {
            self::DODO => 'Dodo',
            self::RACECAR => 'Bolide',
            self::CROWN => 'Couronne',
            self::UNICORN => 'Licorne',
            self::RAINBOW => 'Arc-en-ciel',
            self::PEACH => 'Pêche',
            self::NAILS => 'Manucure',
            self::DRAGON => 'Dragon',
            self::ROLLER => 'Roller',
            self::MASKS => 'Drama',
            self::JUICE => 'Jus de fruit',
            self::FIRE => 'Feu',
        };
    }
}
