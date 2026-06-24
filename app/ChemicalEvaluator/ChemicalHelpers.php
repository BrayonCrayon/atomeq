<?php


namespace App\ChemicalEvaluator;

use App\Models\Element;
use App\Models\Valency;
use Cache;


trait ChemicalHelpers {
    function calculateValency(string $element): int
    {
        // TODO: Get valency from element valence value (8 - valence);
        return 0;
    }

    function valencyLookup(string $element): array
    {
        $elements = Cache::get('valency-lookup', function () {
            return Element::get()->keyBy('symbol');
        });

        $valencies = Valency::query()
            ->where('element_id', $elements[$element]->id)
            ->get()
            ->toArray();

        return $valencies;
    }
}
