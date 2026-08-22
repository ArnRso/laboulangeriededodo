<?php

namespace App\Form\AppDetails;

use App\Enum\AppKind;
use Symfony\Component\Form\AbstractType;

/**
 * Associe chaque application imitée à son formulaire de détails et à ses
 * valeurs de départ. Les applications sont pilotées par le code : ajouter
 * un cas ici, un gabarit d'ouverture, et c'est tout.
 */
final class AppDetailsRegistry
{
    /**
     * @return class-string<AbstractType<mixed>>
     */
    public function formTypeFor(AppKind $appKind): string
    {
        return match ($appKind) {
            AppKind::UBER_EATS => UberEatsDetailsType::class,
            AppKind::INSTAGRAM => InstagramDetailsType::class,
            AppKind::TINDER => TinderDetailsType::class,
            AppKind::DOCTOLIB => DoctolibDetailsType::class,
        };
    }

    /**
     * Valeurs proposées à la création, pour que le formulaire parle déjà la
     * langue de l'app et que l'admin n'ait qu'à personnaliser.
     *
     * @return array<string, mixed>
     */
    public function defaultsFor(AppKind $appKind): array
    {
        return match ($appKind) {
            AppKind::UBER_EATS => [
                'courier' => 'Dodo du passé',
                'trip' => 'Ton adolescence → Aujourd\'hui · 11 ans de trajet',
                'stars' => 5,
            ],
            AppKind::INSTAGRAM => [
                'username' => 'dodo.du.passe',
                'location' => '',
                'likedBy' => 'ta.mere',
                'likesCount' => 1240,
                'hashtags' => '#LoreUnlocked #CanonEvent',
                'comments' => '',
                'timeAgo' => 'Il y a 11 ans',
                'badge' => '',
            ],
            AppKind::TINDER => [
                'matchName' => '',
                'matchAge' => 19,
                'matchEmoji' => '💘',
                'locationLine' => '📍 À 11 ans de toi · Encore en ligne, malheureusement',
                'dramaLevel' => 87,
                'chips' => "🚩 Red flag\n🎭 Canon event",
            ],
            AppKind::DOCTOLIB => [
                'practitioner' => 'Dr Passé',
                'specialty' => 'Spécialiste des décisions catastrophiques',
                'sector' => 'Conventionné secteur 2015',
                'address' => "Ton adolescence\n2e étage, porte du fond",
                'refundLabel' => 'Pris en charge par la mutuelle du passé',
            ],
        };
    }
}
