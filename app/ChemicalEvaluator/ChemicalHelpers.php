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

        if (!isset($elements[$element])) {
            return collect();
        }

        return $elements[$element]->valencies;
    }

    function electronegativeLookup(string $element): float
    {
        $elements = Cache::remember('electronegative-lookup', 3600, function () {
            return Element::get()
                ->keyBy('symbol');
        });

        if (!isset($elements[$element])) {
            return 0;
        }

        return $elements[$element]->electronegativity;
    }
}
