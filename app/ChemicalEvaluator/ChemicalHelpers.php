<?php


namespace App\ChemicalEvaluator;

use App\Models\Element;
use Cache;
use Illuminate\Support\Collection;


trait ChemicalHelpers {
    function calculateValency(Substance $leftSubstance, Substance $rightSubstance): int
    {
        $leftLowestValency = $leftSubstance->valencies->min('valency');
        $rightLowestValency = $rightSubstance->valencies->min('valency');
        $atomsOfLeft = gmp_strval($rightLowestValency / (gmp_gcd($leftLowestValency, $rightLowestValency)));

        return (int)$atomsOfLeft;
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
