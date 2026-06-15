<?php


namespace App\ChemicalEvaluator;

use App\Models\Element;
use Cache;


trait ChemicalHelpers {
    function calculateValency(string $element): int
    {
        // TODO: Get valency from element valence value (8 - valence);
        return 0;
    }

    function valencyLookup(string $element): int
    {
        $elements = Cache::get('valency-lookup', function () {
            return Element::get()->keyBy('symbol');
        });

        return $elements[$element]->valency;
    }
}
