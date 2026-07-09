<?php

namespace Tests\Feature\ChemicalEvaluator;

use App\ChemicalEvaluator\AdditionOperator;
use App\ChemicalEvaluator\Reactant;
use App\Models\Element;
use App\Models\Valency;
use Cache;

test('will correctly assign atoms based on their valency', function () {
    Cache::flush();
    $ironElement = Element::factory()->create(['name' => 'Iron', 'symbol' => 'Fe']);
    Valency::factory()->for($ironElement)
        ->sequence(
            ['valency' => 2],
            ['valency' => 3]
        )->create();
    $oxygenElement = Element::factory()->create(['name' => 'Oxygen', 'symbol' => 'O']);
    Valency::factory()->for($oxygenElement)
        ->sequence(['valency' => 2])->create();

    $left = new Reactant('Fe');
    $right = new Reactant('O');

    $additionOperator = new AdditionOperator();
    $result = $additionOperator->operate($left, $right);

    expect($result->substances)->toHaveCount(2);
    expect($result->substances[0]->atom)->toBe(1);
    expect($result->substances[1]->atom)->toBe(1);
});

test('will correctly assign atoms based on their valency when multiple valencies is available and a specific valency is provided', function () {
    Cache::flush();
    $ironElement = Element::factory()->create(['name' => 'Copper', 'symbol' => 'Cu']);
    Valency::factory()->for($ironElement)
        ->sequence(
            ['valency' => 1],
            ['valency' => 2]
        )->create();
    $oxygenElement = Element::factory()->create(['name' => 'Oxygen', 'symbol' => 'O']);
    Valency::factory()->for($oxygenElement)
        ->sequence(['valency' => 2])->create();

    $left = new Reactant('Cu');
    $right = new Reactant('O');

    $additionOperator = new AdditionOperator();
    $result = $additionOperator->operate($left, $right);

    expect($result->substances)->toHaveCount(2);
    expect($result->substances[0]->atom)->toBe(2);
    expect($result->substances[1]->atom)->toBe(1);
    expect($result->__toString())->toBe("Cu<sub>2</sub>O");
});

//TODO: Apply Reactivity Series to elements:
// 3. Simplify Using a Single "Activity Series" RankIf your calculator is only dealing with basic single-displacement reactions (like high school chemistry), managing multiple potentials can become overly complex for your code.Instead of storing raw voltage values, you can create a simplified, single database attribute called reactivity_rank. You simply number the elements from 1 (most reactive, like Potassium) to 25+ (least reactive, like Gold) based on the standard Activity Series of Metals.Mg: reactivity_rank = 5Cu: reactivity_rank = 15Logic: If element_A.reactivity_rank < element_B.reactivity_rank, the reaction happens.
