<?php

use App\ChemicalEvaluator\ChemicalHelpers;
use App\ChemicalEvaluator\Substance;
use App\Models\Element;
use App\Models\Valency;

uses(ChemicalHelpers::class);

describe('valencyLookup', function () {
    beforeEach(function () {
        Cache::clear();
    });

    test('can lookup valency of Nitrogen', function () {
        $result = $this->valencyLookup('N');

        expect($result)->toHaveCount(1);
        expect($result[0]->valency)->toBe(3);
    });

    test('can retrieve multiple valencies for an element', function () {
        $element = Element::where('symbol', 'Mn')->first();

        $valencies = $element->valencies;

        $result = $this->valencyLookup('Mn');

        expect($result)->toHaveCount(3);
        expect($result)->toEqual($valencies);
    });
});

describe('calculateAtom', function () {
    test('will calculate Fe valency with O', function () {
        $iron = Element::query()->where('symbol', 'Fe')->first();
        $ironValency = $iron->valencies->first();

        $oxygen = Element::query()->where('symbol', 'O')->first();
        $oxygenValency = $oxygen->valencies->first();

        $result = $this->calculateAtom($oxygenValency->valency, $ironValency->valency);

        expect($result)->toBe(1);
    });

    test('will calculate O valency with Fe', function () {
        $iron = Element::query()->where('symbol', 'Fe')->first();
        $ironValency = $iron->valencies->last();

        $oxygen = Element::query()->where('symbol', 'O')->first();
        $oxygenValency = $oxygen->valencies->first();

        $result = $this->calculateAtom($ironValency->valency, $oxygenValency->valency);

        expect($result)->toBe(2);
    });
});
