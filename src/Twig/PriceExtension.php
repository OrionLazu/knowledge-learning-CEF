<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

/**
 * Twig filter displaying a price stored in cents: {{ 5000|price }} -> "50,00 €".
 */
final class PriceExtension
{
    #[AsTwigFilter('price')]
    public function formatPrice(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }
}
