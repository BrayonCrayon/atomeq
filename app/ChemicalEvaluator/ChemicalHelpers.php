<?php


namespace App\ChemicalEvaluator;

use App\Models\Element;
use App\Models\Valency;
use Cache;
use Illuminate\Support\Collection;


trait ChemicalHelpers {


    const array FUNCTIONAL_CONNECTIVITY = [
        'H' => [
            'F'  => 'preferred',
            'Cl' => 'preferred',
            'Br' => 'preferred',
            'I'  => 'preferred',
            'O'  => 'preferred',
            'N'  => 'preferred',
            'C'  => 'preferred',
            'S'  => 'possible',
            'P'  => 'possible',
        ],

        'C' => [
            'H'  => 'preferred',
            'F'  => 'preferred',
            'Cl' => 'preferred',
            'Br' => 'preferred',
            'I'  => 'preferred',
            'O'  => 'preferred',
            'N'  => 'preferred',
            'S'  => 'preferred',
            'P'  => 'possible',
        ],

        'N' => [
            'H'  => 'preferred',
            'F'  => 'preferred',
            'Cl' => 'preferred',
            'Br' => 'preferred',
            'I'  => 'possible',
            'O'  => 'preferred',
            'C'  => 'preferred',
            'S'  => 'possible',
            'P'  => 'possible',
        ],

        'O' => [
            'H'  => 'preferred',
            'C'  => 'preferred',
            'N'  => 'preferred',
            'P'  => 'preferred',
            'S'  => 'preferred',
            'F'  => 'possible',
            'Cl' => 'possible',
            'Br' => 'possible',
            'I'  => 'possible',
        ],

        'P' => [
            'H'  => 'possible',
            'F'  => 'preferred',
            'Cl' => 'preferred',
            'Br' => 'preferred',
            'I'  => 'preferred',
            'O'  => 'preferred',
            'N'  => 'possible',
            'S'  => 'possible',
        ],

        'S' => [
            'H'  => 'possible',
            'F'  => 'preferred',
            'Cl' => 'preferred',
            'Br' => 'preferred',
            'I'  => 'preferred',
            'O'  => 'preferred',
            'N'  => 'possible',
            'C'  => 'preferred',
            'P'  => 'possible',
        ],

        'Se' => [
            'H'  => 'possible',
            'F'  => 'preferred',
            'Cl' => 'preferred',
            'Br' => 'preferred',
            'I'  => 'preferred',
            'O'  => 'preferred',
            'N'  => 'possible',
            'C'  => 'preferred',
            'S'  => 'possible',
        ],

        'F' => [
            'H'  => 'preferred',
            'C'  => 'preferred',
            'N'  => 'preferred',
            'P'  => 'preferred',
            'S'  => 'preferred',
            'Se' => 'preferred',
            'Cl' => 'preferred',
            'Br' => 'preferred',
            'I'  => 'preferred',
        ],

        'Cl' => [
            'H'  => 'preferred',
            'C'  => 'preferred',
            'N'  => 'possible',
            'P'  => 'preferred',
            'S'  => 'preferred',
            'Se' => 'preferred',
            'F'  => 'preferred',
            'Br' => 'preferred',
            'I'  => 'preferred',
        ],

        'Br' => [
            'H'  => 'preferred',
            'C'  => 'preferred',
            'N'  => 'possible',
            'P'  => 'preferred',
            'S'  => 'preferred',
            'Se' => 'preferred',
            'F'  => 'preferred',
            'Cl' => 'preferred',
            'I'  => 'preferred',
        ],

        'I' => [
            'H'  => 'preferred',
            'C'  => 'preferred',
            'N'  => 'possible',
            'P'  => 'preferred',
            'S'  => 'preferred',
            'Se' => 'preferred',
            'F'  => 'preferred',
            'Cl' => 'preferred',
            'Br' => 'preferred',
        ],
    ];

    function calculateAtom(int $leftValency, int $rightValency): int
    {
        $atomsOfLeft = gmp_strval($rightValency / (gmp_gcd($leftValency, $rightValency)));

        return (int)$atomsOfLeft;
    }

    /**
     * @return Collection<int, Valency>
     * */
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
