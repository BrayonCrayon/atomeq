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
