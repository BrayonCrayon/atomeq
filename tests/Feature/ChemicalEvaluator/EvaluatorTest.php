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
            ->and($result)->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(Reactant::class);
    });

    test('it can evaluate a simple reaction to determine the product', function () {
        $equation = "H<sub>2</sub> + O =";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(Reactant::class)
            ->and($result[0]->substances)->toHaveCount(2)
            ->and($result[0]->substances[0]->element)->toBe('H')
            ->and($result[0]->substances[0]->atom)->toBe(2)
            ->and($result[0]->substances[1]->element)->toBe('O')
            ->and($result[0]->substances[1]->atom)->toBe(1);
    });
});
