<?php

use App\ChemicalEvaluator\Reactant;

describe('Reactant', function () {
    test('will create a reactant', function () {
        $target = new Reactant('H');

        expect($target->substance)->toBe('H')
            ->and($target->coefficient)->toBe(1)
            ->and($target->atom)->toBe(1);
    });

    test('will create a reactant with a coefficient', function () {
       $substance = '2H';
       $target = new Reactant($substance);

       expect($target->substance)->toBe('H')
            ->and($target->coefficient)->toBe(2)
            ->and($target->atom)->toBe(1);
    });

    test('will throw an exception when no substance is provided', function () {
        $this->expectException(InvalidArgumentException::class);
        new Reactant('2');
    });

    test('will throw an exception when the substance is empty', function () {
        $this->expectException(InvalidArgumentException::class);
        new Reactant('');
    });
});
