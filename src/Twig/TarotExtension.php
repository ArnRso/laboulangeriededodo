<?php

namespace App\Twig;

use App\Enum\TarotCard;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Donne au gabarit du tarot la carte correspondant à la valeur saisie, sans
 * qu'il ait à connaître l'enum ni à se défendre d'une valeur inconnue.
 */
class TarotExtension extends AbstractExtension
{
    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('tarot_card', $this->card(...)),
        ];
    }

    /**
     * @return array{label: string, numeral: string, symbol: string, meaning: string}
     */
    public function card(string $value): array
    {
        $card = TarotCard::tryFrom($value) ?? TarotCard::ARCANE_SANS_NOM;

        return [
            'label' => $card->label(),
            'numeral' => $card->numeral(),
            'symbol' => $card->symbol(),
            'meaning' => $card->meaning(),
        ];
    }
}
