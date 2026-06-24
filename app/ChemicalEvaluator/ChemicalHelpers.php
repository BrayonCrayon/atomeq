<?php


namespace App\ChemicalEvaluator;

use App\Models\Element;
use Cache;
use Illuminate\Support\Collection;


trait ChemicalHelpers {
    function calculateValency(string $element): int
    {
        // TODO: Get valency from element valence value (8 - valence);
        return 0;
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
