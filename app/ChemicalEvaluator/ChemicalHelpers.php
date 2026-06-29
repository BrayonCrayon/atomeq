<?php


namespace App\ChemicalEvaluator;

use App\Models\Element;
use Cache;
use Illuminate\Support\Collection;


trait ChemicalHelpers {
    function calculateValency(string $leftElement, string $rightElement): int
    {
        // TODO: Get valency from element valence value (8 - valence);
        $possibleLeftValencies = $this->valencyLookup($leftElement);
        $possibleRightValencies = $this->valencyLookup($rightElement);

        /**
         * atoms of A = v_b / GCD(vₐ, v_b)
         * atoms of B = vₐ / GCD(vₐ, v_b)
         */
        $atomsOfA = gmp_strval($possibleRightValencies->first()->valency / (gmp_gcd($possibleLeftValencies->first()->valency, $possibleRightValencies->first()->valency)));

        return (int)$atomsOfA;
    }

    function valencyLookup(string $element): Collection
    {
        $elements = Cache::remember('valency-lookup', 3600, function () {
            return Element::get()->load('valencies')
                ->keyBy('symbol');
        });

        return $elements[$element]->valencies;
    }
}
