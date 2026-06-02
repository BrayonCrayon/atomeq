<?php


use App\ChemicalEvaluator\AdditionOperator;
use App\ChemicalEvaluator\Reactant;
use App\ChemicalEvaluator\Tokenizer;

describe('Tokenizer', function () {

    test('will tokenize a chemical equation string', function () {
        $equation = "H + O";
        $tokenizer = new Tokenizer();

        $tokenizer->tokenize($equation);

        expect($tokenizer->tokens)->toBeArray()
            ->and($tokenizer->tokens)->toHaveCount(3)
            ->and($tokenizer->tokens[0])->toBeInstanceOf(Reactant::class)
            ->and($tokenizer->tokens[0]->substances[0]->element)->toBe('H')
            ->and($tokenizer->tokens[1])->toBeInstanceOf(AdditionOperator::class)
            ->and($tokenizer->tokens[2])->toBeInstanceOf(Reactant::class)
            ->and($tokenizer->tokens[2]->substances[0]->element)->toBe('O');
    });

    test('will throw an exception when the equation is empty', function () {
        $tokenizer = new Tokenizer();

        $this->expectException(InvalidArgumentException::class);
        $tokenizer->tokenize("");
    });

    test('will organize the tokens into reverse polish notation', function () {
       $equation = "H + O";
       $tokenizer = new Tokenizer();
       $tokenizer->tokenize($equation);

       $tokenizer->organize();

       expect($tokenizer->stack)->toBeInstanceOf(SplStack::class)
           ->and($tokenizer->stack)->toHaveCount(3)
           ->and($tokenizer->stack[0])->toBeInstanceOf(AdditionOperator::class)
           ->and($tokenizer->stack[1])->toBeInstanceOf(Reactant::class)
           ->and($tokenizer->stack[1]->substances[0]->element)->toBe('O')
           ->and($tokenizer->stack[2])->toBeInstanceOf(Reactant::class)
           ->and($tokenizer->stack[2]->substances[0]->element)->toBe('H');
    });
});
