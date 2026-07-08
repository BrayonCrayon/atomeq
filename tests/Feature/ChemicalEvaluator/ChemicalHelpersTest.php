<?php

use App\ChemicalEvaluator\ChemicalHelpers;
use App\ChemicalEvaluator\Substance;
use App\Models\Element;
use App\Models\Valency;

uses(ChemicalHelpers::class);

describe('valencyLookup', function () {
    test('can lookup valency of Nitrogen', function () {
        Element::factory()->hasValencies(['valency' => 3])->create([
            'name' => 'Nitrogen',
            'symbol' => 'N',
        ]);

        $result = $this->valencyLookup('N');

        expect($result)->toHaveCount(1);
        expect($result[0]->valency)->toBe(3);
    });

    test('can retrieve multiple valencies for an element', function () {
        $element = Element::factory()->hasValencies(3)->create([
            'name' => 'Nitrogen',
            'symbol' => 'N',
        ]);

        $valencies = $element->valencies;

        $result = $this->valencyLookup('N');

        expect($result)->toHaveCount(3);
        expect($result)->toEqual($valencies);
    });
});

describe('calculateValency', function () {
   test('will calculate Fe valency with O', function () {
        $iron = Element::factory()->create([
            'name' => 'Iron',
            'symbol' => 'Fe',
            'atomic_number' => 21
        ]);
        $ironValency = Valency::factory()->count(2)->for($iron)->sequence(
            ['valency' => 2],
            ['valency' => 3]
        )->create()->first();

        $oxygen = Element::factory()->hasValencies([ 'valency' => 2 ])->create([
            'name' => 'Oxygen',
            'symbol' => 'O',
            'atomic_number' => 8
        ]);
        $oxygenValency = $oxygen->valencies->first();

       $result = $this->calculateValency($oxygenValency->valency, $ironValency->valency);

       expect($result)->toBe(1);
    });

   test('will calculate O valency with Fe', function() {
       $iron = Element::factory()->create([
           'name' => 'Iron',
           'symbol' => 'Fe',
           'atomic_number' => 21
       ]);
       $ironValency = Valency::factory()->count(2)->for($iron)->sequence(
           ['valency' => 2],
           ['valency' => 3]
       )->create()->last();
       $oxygen = Element::factory()->hasValencies([ 'valency' => 2 ])->create([
           'name' => 'Oxygen',
           'symbol' => 'O',
           'atomic_number' => 8
       ]);
       $oxygenValency = $oxygen->valencies->first();

       $result = $this->calculateValency($ironValency->valency, $oxygenValency->valency);

       expect($result)->toBe(2);
    });
});
