<?php

namespace App\Form\AppDetails;

use App\Enum\AppKind;

/**
 * Associe chaque application imitée à son formulaire de détails. Les
 * applications sont pilotées par le code : ajouter un cas dans AppKind, un
 * type ici, un gabarit d'ouverture et une feuille de style, et c'est tout.
 */
final class AppDetailsRegistry
{
    /**
     * @return class-string<AbstractAppDetailsType>
     */
    public function formTypeFor(AppKind $appKind): string
    {
        return match ($appKind) {
            AppKind::UBER_EATS => UberEatsDetailsType::class,
            AppKind::INSTAGRAM => InstagramDetailsType::class,
            AppKind::TINDER => TinderDetailsType::class,
            AppKind::DOCTOLIB => DoctolibDetailsType::class,
            AppKind::TIKTOK => TikTokDetailsType::class,
            AppKind::SNAPCHAT => SnapchatDetailsType::class,
            AppKind::X => XDetailsType::class,
            AppKind::BEREAL => BeRealDetailsType::class,
            AppKind::YOUTUBE => YouTubeDetailsType::class,
            AppKind::NETFLIX => NetflixDetailsType::class,
            AppKind::SPOTIFY => SpotifyDetailsType::class,
            AppKind::WHATSAPP => WhatsAppDetailsType::class,
            AppKind::MESSENGER => MessengerDetailsType::class,
            AppKind::IMESSAGE => IMessageDetailsType::class,
            AppKind::DUOLINGO => DuolingoDetailsType::class,
            AppKind::HINGE => HingeDetailsType::class,
            AppKind::BUMBLE => BumbleDetailsType::class,
            AppKind::UBER => UberDetailsType::class,
            AppKind::DELIVEROO => DeliverooDetailsType::class,
            AppKind::BURGER_KING => BurgerKingDetailsType::class,
            AppKind::MCDONALDS => McDonaldsDetailsType::class,
            AppKind::WAZE => WazeDetailsType::class,
            AppKind::REVOLUT => RevolutDetailsType::class,
            AppKind::PAYPAL => PayPalDetailsType::class,
            AppKind::LYDIA => LydiaDetailsType::class,
            AppKind::METEO => MeteoDetailsType::class,
            AppKind::CALENDAR => CalendarDetailsType::class,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultsFor(AppKind $appKind): array
    {
        return $this->formTypeFor($appKind)::defaults();
    }
}
