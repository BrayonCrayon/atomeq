<?php

namespace Tests\Feature\ChemicalEvaluator;

use App\ChemicalEvaluator\AdditionOperator;
use App\ChemicalEvaluator\Reactant;
use Cache;

test('will correctly assign atoms based on their valency', function () {
    Cache::flush();

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

    $left = new Reactant('Cu');
    $right = new Reactant('O');

    $additionOperator = new AdditionOperator();
    $result = $additionOperator->operate($left, $right);

    expect($result->substances)->toHaveCount(2);
    expect($result->substances[0]->atom)->toBe(2);
    expect($result->substances[1]->atom)->toBe(1);
    expect($result->__toString())->toBe("Cu<sub>2</sub>O");
});

test('will order cation before anion regardless of input order', function () {
    Cache::flush();

    $left = new Reactant('O');
    $right = new Reactant('Cu');

    $additionOperator = new AdditionOperator();
    $result = $additionOperator->operate($left, $right);

    expect($result->__toString())->toBe("Cu<sub>2</sub>O");
});
