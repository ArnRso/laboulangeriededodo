<?php

namespace App\Enum;

/**
 * Durée de validité d'un lien d'invitation généré à la main. Les paliers
 * s'espacent à mesure qu'on s'éloigne : on choisit finement une échéance
 * proche, grossièrement une échéance lointaine.
 */
enum InvitationLifetime: int
{
    case HALF_HOUR = 30;
    case ONE_HOUR = 60;
    case TWO_HOURS = 120;
    case SIX_HOURS = 360;
    case TWELVE_HOURS = 720;
    case ONE_DAY = 1440;
    case TWO_DAYS = 2880;
    case THREE_DAYS = 4320;
    case ONE_WEEK = 10080;
    case TWO_WEEKS = 20160;

    public function label(): string
    {
        return match ($this) {
            self::HALF_HOUR => '30 minutes',
            self::ONE_HOUR => '1 heure',
            self::TWO_HOURS => '2 heures',
            self::SIX_HOURS => '6 heures',
            self::TWELVE_HOURS => '12 heures',
            self::ONE_DAY => '24 heures',
            self::TWO_DAYS => '2 jours',
            self::THREE_DAYS => '3 jours',
            self::ONE_WEEK => '1 semaine',
            self::TWO_WEEKS => '2 semaines',
        };
    }

    public function interval(): \DateInterval
    {
        return new \DateInterval(sprintf('PT%dM', $this->value));
    }
}
