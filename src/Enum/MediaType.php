<?php

namespace App\Enum;

enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case TEXT = 'text';
    case LINK = 'link';

    public function label(): string
    {
        return match ($this) {
            self::IMAGE => 'Photo',
            self::VIDEO => 'Vidéo',
            self::AUDIO => 'Audio',
            self::TEXT => 'Texte',
            self::LINK => 'Lien',
        };
    }

    public function isFile(): bool
    {
        return in_array($this, [self::IMAGE, self::VIDEO, self::AUDIO], true);
    }
}
