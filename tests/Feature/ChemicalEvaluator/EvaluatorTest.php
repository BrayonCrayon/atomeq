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

    test('it returns the combined reactants as the product when no explicit product is given', function () {
        $equation = "H<sub>2</sub> + O =";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBeString()
            ->and($result)->toBe("H<sub>2</sub>O");
    });

    test('it returns the combined reactants for Na + Cl when no explicit product is given', function () {
        $equation = "Na + Cl =";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBeString()
            ->and($result)->toBe("NaCl");
    });

    test('it merges same-element reactants into a single subscripted substance', function () {
        $equation = "H + H =";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBeString()
            ->and($result)->toBe("H<sub>2</sub>");
    });

    test('it correctly chains three reactants', function () {
        $equation = "H + H + O =";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBeString()
            ->and($result)->toBe("H<sub>2</sub>O");
    });

    test('it applies a coefficient to a non-subscripted substance when combining', function () {
        $equation = "2Na + Cl =";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBeString()
            ->and($result)->toBe("2NaCl");
    });

    test('it correctly chains four reactants preserving insertion order', function () {
        $equation = "Na + Cl + H + O =";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBeString()
            ->and($result)->toBe("NaClHO");
    });

    test('will correctly equate a synthesis equation', function () {
        $equation = "N<sub>2</sub> + H<sub>2</sub> ->";
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($equation);
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toEqual('NH<sub>3</sub>');
    });
});
