<?php

use App\ChemicalEvaluator\ChemicalHelpers;
use App\Models\Element;

uses(ChemicalHelpers::class);

describe('valencyLookup', function () {
    test('can lookup valency of Nitrogen', function () {
        Element::factory()->create([
            'name' => 'Nitrogen',
            'symbol' => 'N',
            'valency' => 3
        ]);
        $result = $this->valencyLookup('N');
        expect($result)->toBe(3);
    });
});

describe('calculateValency', function () {
   test('will calculate N valency with H', function () {
        $Scandium = Element::factory()->create([
            'name' => 'Scandium',
            'symbol' => 'Sc',
            'valency' => null,
            'atomic_number' => 21
        ]);
        $oxygen = Element::factory()->create([
            'name' => 'Oxygen',
            'symbol' => 'O',
            'valency' => 2,
            'atomic_number' => 8
        ]);

        $result = $this->calculateValency($element, $element);
        expect($result)->toBe(4);
    });
});
