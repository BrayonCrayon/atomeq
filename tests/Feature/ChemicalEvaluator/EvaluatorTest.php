<?php

use App\ChemicalEvaluator\Evaluator;
use App\ChemicalEvaluator\Tokenizer;
use App\ChemicalEvaluator\Reactant;

describe('Evaluator', function () {
    test('it can evaluate a complex equation with multiple additions and a reaction', function () {
        $equation = "H<sub>2</sub> + O = H<sub>2</sub>O";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(3)
            ->and($result[0])->toBeInstanceOf(Reactant::class)
            ->and($result[1])->toBeInstanceOf(Reactant::class)
            ->and($result[2])->toBeInstanceOf(Reactant::class);
    });
});
