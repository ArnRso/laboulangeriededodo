<?php

namespace App\Twig;

use App\Enum\TarotCard;
use Twig\Attribute\AsTwigFunction;

/**
 * Donne au gabarit du tarot la carte correspondant à la valeur saisie, sans
 * qu'il ait à connaître l'enum ni à se défendre d'une valeur inconnue.
 */
class TarotExtension
{
    /**
     * @return array{label: string, numeral: string, symbol: string, meaning: string}
     */
    #[AsTwigFunction(name: 'tarot_card')]
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
