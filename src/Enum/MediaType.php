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

    /**
     * Icône Bootstrap Icons associée, pour les onglets de l'administration.
     */
    public function icon(): string
    {
        return match ($this) {
            self::IMAGE => 'image',
            self::VIDEO => 'camera-video',
            self::AUDIO => 'music-note-beamed',
            self::TEXT => 'card-text',
            self::LINK => 'link-45deg',
        };
    }

    public function isFile(): bool
    {
        return in_array($this, [self::IMAGE, self::VIDEO, self::AUDIO], true);
    }

    /**
     * Préfixe MIME attendu, pour vérifier qu'un fichier correspond bien au type
     * choisi : une vidéo déposée comme photo ne s'afficherait pas.
     */
    public function mimePrefix(): ?string
    {
        return match ($this) {
            self::IMAGE => 'image/',
            self::VIDEO => 'video/',
            self::AUDIO => 'audio/',
            self::TEXT, self::LINK => null,
        };
    }

    /**
     * Valeur de l'attribut accept, pour que le sélecteur de fichiers filtre.
     */
    public function acceptAttribute(): ?string
    {
        $prefix = $this->mimePrefix();

        return null === $prefix ? null : $prefix.'*';
    }
}
