<?php


namespace App\ChemicalEvaluator;

use App\Models\Element;
use Cache;
use Illuminate\Support\Collection;


trait ChemicalHelpers {
    function calculateAtom(int $leftValency, int $rightValency): int
    {
        $atomsOfLeft = gmp_strval($rightValency / (gmp_gcd($leftValency, $rightValency)));

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
