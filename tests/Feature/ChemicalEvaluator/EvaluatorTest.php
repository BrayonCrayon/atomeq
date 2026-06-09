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

        expect($result)->toBeString()
            ->and($result)->toBe("H<sub>2</sub>O");
    });

    test('it can evaluate a simple reaction to determine the product', function () {
        $equation = "H<sub>2</sub> + O =";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBeString()
            ->and($result)->toBe("H<sub>2</sub>O");
    });
});
