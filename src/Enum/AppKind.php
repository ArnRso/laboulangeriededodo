<?php

namespace App\Enum;

/**
 * Application dont une notification emprunte l'habillage. La liste est pilotée
 * par le code : chaque cas a son formulaire de détails côté admin, son gabarit
 * d'ouverture et sa feuille de style côté destinataire.
 */
enum AppKind: string
{
    case UBER_EATS = 'uber_eats';
    case INSTAGRAM = 'instagram';
    case TINDER = 'tinder';
    case DOCTOLIB = 'doctolib';
    case TIKTOK = 'tiktok';
    case SNAPCHAT = 'snapchat';
    case X = 'x';
    case BEREAL = 'bereal';
    case YOUTUBE = 'youtube';
    case NETFLIX = 'netflix';
    case SPOTIFY = 'spotify';
    case WHATSAPP = 'whatsapp';
    case MESSENGER = 'messenger';
    case IMESSAGE = 'imessage';
    case DUOLINGO = 'duolingo';
    case HINGE = 'hinge';
    case BUMBLE = 'bumble';
    case UBER = 'uber';
    case DELIVEROO = 'deliveroo';
    case BURGER_KING = 'burger_king';
    case MCDONALDS = 'mcdonalds';
    case WAZE = 'waze';
    case REVOLUT = 'revolut';
    case PAYPAL = 'paypal';
    case LYDIA = 'lydia';
    case METEO = 'meteo';
    case CALENDAR = 'calendar';

    public function label(): string
    {
        return match ($this) {
            self::UBER_EATS => 'Uber Eats',
            self::INSTAGRAM => 'Instagram',
            self::TINDER => 'Tinder',
            self::DOCTOLIB => 'Doctolib',
            self::TIKTOK => 'TikTok',
            self::SNAPCHAT => 'Snapchat',
            self::X => 'X',
            self::BEREAL => 'BeReal',
            self::YOUTUBE => 'YouTube',
            self::NETFLIX => 'Netflix',
            self::SPOTIFY => 'Spotify',
            self::WHATSAPP => 'WhatsApp',
            self::MESSENGER => 'Messenger',
            self::IMESSAGE => 'iMessage',
            self::DUOLINGO => 'Duolingo',
            self::HINGE => 'Hinge',
            self::BUMBLE => 'Bumble',
            self::UBER => 'Uber',
            self::DELIVEROO => 'Deliveroo',
            self::BURGER_KING => 'Burger King',
            self::MCDONALDS => 'McDonald\'s',
            self::WAZE => 'Waze',
            self::REVOLUT => 'Revolut',
            self::PAYPAL => 'PayPal',
            self::LYDIA => 'Lydia',
            self::METEO => 'Météo',
            self::CALENDAR => 'Calendrier',
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
            self::TIKTOK => 'Une vidéo qui cartonne : plein écran, compteurs de likes, son original, légende à hashtags.',
            self::SNAPCHAT => 'Un Snap reçu d\'un ami : photo éphémère, légende en bandeau, flamme de streak.',
            self::X => 'Un post : texte qui claque, média, statistiques absurdes de reposts et de vues.',
            self::BEREAL => '⚠️ Time to BeReal ⚠️ : la photo du moment, en retard de plusieurs années, avec les RealMojis des amis.',
            self::YOUTUBE => 'Une vidéo mise en ligne : lecteur, titre, chaîne, vues et description.',
            self::NETFLIX => 'Une recommandation : affiche, pourcentage de correspondance, Top 10, synopsis.',
            self::SPOTIFY => 'Un titre en lecture : pochette, artiste, barre de progression, paroles.',
            self::WHATSAPP => 'Une conversation : bulles vertes, média envoyé, réponses absurdes.',
            self::MESSENGER => 'Une conversation : bulles bleues dégradées, média envoyé, réponses absurdes.',
            self::IMESSAGE => 'Une conversation : bulles bleues et grises, « Distribué », média envoyé.',
            self::DUOLINGO => 'Un rappel du hibou : ça fait N jours que tu n\'as pas… La leçon du jour est le souvenir.',
            self::HINGE => 'Une réponse à un prompt que quelqu\'un a aimée : nom, photo, question et réponse.',
            self::BUMBLE => 'Un match : tu as 24 h pour envoyer le premier message. Profil et étiquettes.',
            self::UBER => 'Une course terminée : trajet, chauffeur, voiture, reçu en aura.',
            self::DELIVEROO => 'Une commande suivie étape par étape jusqu\'à la livraison, avec le reçu.',
            self::BURGER_KING => 'Une commande prête à récupérer : numéro, menu, couronne du jour.',
            self::MCDONALDS => 'Une commande prête : code à trois lettres, menu, « c\'est tout ce que j\'aime ».',
            self::WAZE => 'Un itinéraire : destination, heure d\'arrivée, alertes sur la route (radars de nostalgie).',
            self::REVOLUT => 'Une transaction : montant en aura, bénéficiaire, référence, reçu joint.',
            self::PAYPAL => 'Un paiement reçu : montant en aura, expéditeur, petit mot.',
            self::LYDIA => 'Un virement entre amis : montant en aura, message avec emojis.',
            self::METEO => 'Un bulletin : ville, température, conditions (risque de drama), prévisions heure par heure.',
            self::CALENDAR => 'Un événement : date, horaire, lieu, participants, notes.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::UBER_EATS => '🛵',
            self::INSTAGRAM => '📷',
            self::TINDER => '🔥',
            self::DOCTOLIB => '🩺',
            self::TIKTOK => '🎵',
            self::SNAPCHAT => '👻',
            self::X => '✖️',
            self::BEREAL => '📸',
            self::YOUTUBE => '▶️',
            self::NETFLIX => '🎬',
            self::SPOTIFY => '🎧',
            self::WHATSAPP => '💬',
            self::MESSENGER => '💙',
            self::IMESSAGE => '🗨️',
            self::DUOLINGO => '🦉',
            self::HINGE => '🖤',
            self::BUMBLE => '🐝',
            self::UBER => '🚗',
            self::DELIVEROO => '🦘',
            self::BURGER_KING => '👑',
            self::MCDONALDS => '🍟',
            self::WAZE => '🚘',
            self::REVOLUT => '💳',
            self::PAYPAL => '💰',
            self::LYDIA => '💸',
            self::METEO => '⛅',
            self::CALENDAR => '📅',
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
            self::TIKTOK => 'linear-gradient(135deg, #25F4EE, #000 45%, #FE2C55)',
            self::SNAPCHAT => '#FFFC00',
            self::X => '#000000',
            self::BEREAL => '#000000',
            self::YOUTUBE => '#FF0000',
            self::NETFLIX => '#E50914',
            self::SPOTIFY => '#1DB954',
            self::WHATSAPP => '#25D366',
            self::MESSENGER => 'linear-gradient(135deg, #0099FF, #A033FF 60%, #FF5280)',
            self::IMESSAGE => '#34C759',
            self::DUOLINGO => '#58CC02',
            self::HINGE => '#3A2232',
            self::BUMBLE => '#FFC629',
            self::UBER => '#000000',
            self::DELIVEROO => '#00CCBC',
            self::BURGER_KING => '#D62300',
            self::MCDONALDS => '#DA291C',
            self::WAZE => '#33CCFF',
            self::REVOLUT => '#191C1F',
            self::PAYPAL => '#003087',
            self::LYDIA => '#1B69FF',
            self::METEO => 'linear-gradient(180deg, #4A90E2, #87CEEB)',
            self::CALENDAR => '#FF3B30',
        };
    }

    /**
     * Rubrique de la page de choix dans l'admin.
     */
    public function category(): string
    {
        return match ($this) {
            self::INSTAGRAM, self::TIKTOK, self::SNAPCHAT, self::X, self::BEREAL, self::YOUTUBE => 'Réseaux sociaux',
            self::WHATSAPP, self::MESSENGER, self::IMESSAGE => 'Messageries',
            self::TINDER, self::HINGE, self::BUMBLE => 'Rencontres',
            self::SPOTIFY, self::NETFLIX => 'Streaming',
            self::UBER_EATS, self::DELIVEROO, self::BURGER_KING, self::MCDONALDS, self::UBER, self::WAZE => 'Livraison et transport',
            self::REVOLUT, self::PAYPAL, self::LYDIA => 'Argent',
            self::DOCTOLIB, self::DUOLINGO, self::METEO, self::CALENDAR => 'Quotidien',
        };
    }

    /**
     * Les applications regroupées par rubrique, dans l'ordre de déclaration.
     *
     * @return array<string, list<self>>
     */
    public static function byCategory(): array
    {
        $grouped = [];

        foreach (self::cases() as $case) {
            $grouped[$case->category()][] = $case;
        }

        return $grouped;
    }

    /**
     * La ligne d'accroche de la notification dans le fil, avant ouverture.
     *
     * @param array<string, mixed> $data Les détails saisis pour l'app
     */
    public function headline(array $data): string
    {
        return match ($this) {
            self::UBER_EATS => 'Ta commande est arrivée 🎁',
            self::INSTAGRAM => sprintf('%s a publié une photo', self::text($data, 'username', 'dodo.du.passe')),
            self::TINDER => 'C\'est un match ! 💘',
            self::DOCTOLIB => sprintf('Rappel de rendez-vous · %s', self::text($data, 'practitioner', 'Dr Passé')),
            self::TIKTOK => sprintf('@%s · ta vidéo cartonne 🔥', self::text($data, 'username', 'dodo.du.passe')),
            self::SNAPCHAT => sprintf('%s t\'a envoyé un Snap 👻', self::text($data, 'sender', 'Dodo du passé')),
            self::X => sprintf('%s a posté', self::text($data, 'displayName', 'Dodo du passé')),
            self::BEREAL => '⚠️ Time to BeReal ⚠️',
            self::YOUTUBE => sprintf('%s a mis en ligne une vidéo', self::text($data, 'channel', 'Dodo du passé')),
            self::NETFLIX => 'Nouveauté recommandée pour toi',
            self::SPOTIFY => sprintf('Nouveau titre dans « %s »', self::text($data, 'playlist', 'Tes années lycée')),
            self::WHATSAPP, self::MESSENGER, self::IMESSAGE => sprintf('%s t\'a envoyé un message', self::text($data, 'contact', 'Dodo du passé')),
            self::DUOLINGO => sprintf('🦉 Ça fait %s jours que tu n\'as pas pratiqué', self::text($data, 'streakDays', '11')),
            self::HINGE => sprintf('%s a aimé ta réponse', self::text($data, 'name', 'Quelqu\'un')),
            self::BUMBLE => sprintf('Nouveau match 🐝 · plus que %s h', self::text($data, 'hoursLeft', '24')),
            self::UBER => sprintf('Ta course avec %s est terminée', self::text($data, 'driver', 'Dodo du passé')),
            self::DELIVEROO => sprintf('Ta commande %s est livrée', self::text($data, 'restaurant', 'Chez Dodo')),
            self::BURGER_KING => sprintf('Commande n° %s prête 👑', self::text($data, 'orderNumber', '2015')),
            self::MCDONALDS => sprintf('Commande %s prête 🍟', self::text($data, 'orderCode', 'D42')),
            self::WAZE => sprintf('Itinéraire vers %s', self::text($data, 'destination', 'ton adolescence')),
            self::REVOLUT => sprintf('%s · transaction', self::text($data, 'counterparty', 'Dodo du passé')),
            self::PAYPAL => sprintf('Tu as reçu un paiement de %s', self::text($data, 'counterparty', 'Dodo du passé')),
            self::LYDIA => sprintf('%s t\'a envoyé de l\'aura 💸', self::text($data, 'counterparty', 'Dodo du passé')),
            self::METEO => sprintf('Alerte météo : %s', self::text($data, 'condition', 'risque de drama')),
            self::CALENDAR => sprintf('Rappel · %s', self::text($data, 'date', 'aujourd\'hui')),
        };
    }

    /**
     * Le bouton d'ouverture, dans le fil.
     */
    public function openLabel(): string
    {
        return match ($this) {
            self::UBER_EATS, self::DELIVEROO, self::BURGER_KING, self::MCDONALDS => 'Ouvrir la commande',
            self::INSTAGRAM => 'Voir la publication',
            self::TINDER => 'Voir le match',
            self::DOCTOLIB => 'Voir le rendez-vous',
            self::TIKTOK, self::YOUTUBE => 'Regarder la vidéo',
            self::SNAPCHAT => 'Ouvrir le Snap',
            self::X => 'Voir le post',
            self::BEREAL => 'Voir le BeReal',
            self::NETFLIX => 'Lecture',
            self::SPOTIFY => 'Écouter',
            self::WHATSAPP, self::MESSENGER, self::IMESSAGE => 'Répondre',
            self::DUOLINGO => 'Reprendre la leçon',
            self::HINGE => 'Voir le profil',
            self::BUMBLE => 'Envoyer le premier message',
            self::UBER => 'Voir le reçu',
            self::WAZE => 'Démarrer',
            self::REVOLUT => 'Voir la transaction',
            self::PAYPAL => 'Voir les détails',
            self::LYDIA => 'Voir le virement',
            self::METEO => 'Voir les prévisions',
            self::CALENDAR => 'Voir l\'événement',
        };
    }

    /**
     * Gabarit de l'écran d'ouverture, côté destinataire.
     */
    public function template(): string
    {
        return sprintf('feed/open/%s.html.twig', $this->value);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function text(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? null;

        if (\is_scalar($value) && '' !== (string) $value) {
            return (string) $value;
        }

        return $default;
    }
}
