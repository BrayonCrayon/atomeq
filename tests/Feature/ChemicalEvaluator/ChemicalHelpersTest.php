<?php

use App\ChemicalEvaluator\ChemicalHelpers;
use App\Models\Element;
use App\Models\Valency;

uses(ChemicalHelpers::class);

describe('valencyLookup', function () {
    test('can lookup valency of Nitrogen', function () {
        $element = Element::factory()->hasValencies(['valency' => 3])->create([
            'name' => 'Nitrogen',
            'symbol' => 'N',
        ]);

        $result = $this->valencyLookup('N');

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0]['valency'])->toBe(3);
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
