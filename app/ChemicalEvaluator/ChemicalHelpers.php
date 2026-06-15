<?php


namespace App\ChemicalEvaluator;

use App\Models\Element;


trait ChemicalHelpers {
    function calculateValency(string $element): int
    {
        // TODO: Get valency from element valence value (8 - valence);
        return 0;
    }

    function valencyLookup(string $element): int
    {
        $elements = Element::get()->keyBy('symbol');

        return $elements[$element]->valency;
    }
}
