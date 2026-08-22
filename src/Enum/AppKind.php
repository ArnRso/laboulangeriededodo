<?php

namespace App\Enum;

/**
 * Application dont une notification emprunte l'habillage. La liste est pilotée
 * par le code : chaque cas a son formulaire de détails côté admin et son
 * gabarit d'ouverture côté destinataire.
 */
enum AppKind: string
{
    case UBER_EATS = 'uber_eats';
    case INSTAGRAM = 'instagram';
    case TINDER = 'tinder';
    case DOCTOLIB = 'doctolib';

    public function label(): string
    {
        return match ($this) {
            self::UBER_EATS => 'Uber Eats',
            self::INSTAGRAM => 'Instagram',
            self::TINDER => 'Tinder',
            self::DOCTOLIB => 'Doctolib',
        };
    }

    /**
     * Ce que la notification raconte, pour guider le choix dans l'admin.
     */
    public function pitch(): string
    {
        return match ($this) {
            self::UBER_EATS => 'Une commande livrée : le souvenir est le plat, la description la note du restaurant, l\'aura le reçu.',
            self::INSTAGRAM => 'Une publication : photo, likes, légende et faux commentaires d\'amis.',
            self::TINDER => 'Un match : le souvenir devient un profil, idéal pour une mauvaise décision à −500 aura.',
            self::DOCTOLIB => 'Un rendez-vous honoré chez Dr Passé : document partagé, compte-rendu, remboursement en aura.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::UBER_EATS => '🛵',
            self::INSTAGRAM => '📷',
            self::TINDER => '🔥',
            self::DOCTOLIB => '🩺',
        };
    }

    /**
     * Couleur de marque, utilisée pour l'icône dans le fil.
     */
    public function color(): string
    {
        return match ($this) {
            self::UBER_EATS => '#06C167',
            self::INSTAGRAM => 'linear-gradient(45deg, #F9CE34, #EE2A7B 50%, #6228D7)',
            self::TINDER => 'linear-gradient(160deg, #FD5564, #EF4A75)',
            self::DOCTOLIB => '#107ACA',
        };
    }

    /**
     * Gabarit de l'écran d'ouverture, côté destinataire.
     */
    public function template(): string
    {
        return sprintf('feed/open/%s.html.twig', $this->value);
    }
}
