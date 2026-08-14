<?php

use App\ChemicalEvaluator\Reactant;
use App\Models\PolyatomicIon;

describe('Reactant', function () {
    test('will create a reactant', function () {
        $target = new Reactant('H');

        expect($target->substances)->toHaveCount(1)
            ->and($target->substances[0]->element)->toBe('H')
            ->and($target->substances[0]->atom)->toBe(1)
            ->and($target->coefficient)->toBe(1);
    });

    test('will create a reactant with a coefficient', function () {
        $substance = '2H';
        $target = new Reactant($substance);

        expect($target->substances)->tohaveCount(1)
            ->and($target->substances[0]->element)->toBe('H')
            ->and($target->substances[0]->atom)->toBe(1)
            ->and($target->coefficient)->toBe(2);
    });

    test('will create a reactant with a complex substance', function () {
        $substance = '2H<sub>2</sub>O';

        $target = new Reactant($substance);

        expect($target->substances)->toHaveCount(2)
            ->and($target->substances[0]->element)->toBe('H')
            ->and($target->substances[0]->atom)->toBe(2)
            ->and($target->substances[1]->element)->toBe('O')
            ->and($target->substances[1]->atom)->toBe(1)
            ->and($target->coefficient)->toBe(2);
    });

    test('will create reactant with multiple substances that each contain more than one atoms', function () {
        $substance = '2H<sub>2</sub>O<sub>2</sub>';

        $target = new Reactant($substance);

        expect($target->substances)->toHaveCount(2)
            ->and($target->substances[0]->element)->toBe('H')
            ->and($target->substances[0]->atom)->toBe(2)
            ->and($target->substances[1]->element)->toBe('O')
            ->and($target->substances[1]->polyatomicSubstances)->toHaveCount(1)
            ->and($target->substances[1]->polyatomicSubstances->first()->element)->toBe('O')
            ->and($target->substances[1]->polyatomicSubstances->first()->atom)->toBe(2)
            ->and($target->coefficient)->toBe(2);
    });

    test('will throw an exception when no substance is provided', function () {
        $this->expectException(InvalidArgumentException::class);
        new Reactant('2');
    });

    test('will throw if the substance provided has invalid characters', function () {
        $this->expectException(InvalidArgumentException::class);
        $substance = '2H!<sub>2</sub>O';

        new Reactant($substance);
    });

    test('will identify a polyatomic ion', function () {
        $substance = 'NH<sub>4</sub>';

        $target = new Reactant($substance);

        expect($target->substances)->toHaveCount(1)
            ->and($target->substances[0]->element)->toEqual('NH')
            ->and($target->substances[0]->polyatomicSubstances)->toHaveCount(2)
            ->and($target->substances[0]->polyatomicSubstances[0]->element)->toEqual('N')
            ->and($target->substances[0]->polyatomicSubstances[0]->atom)->toEqual(1)
            ->and($target->substances[0]->polyatomicSubstances[1]->element)->toEqual('H')
            ->and($target->substances[0]->polyatomicSubstances[1]->atom)->toEqual(4);
    });

    test('will identify a substance with a polyatomic ion', function () {
        $sulfate = PolyatomicIon::whereSymbol('SO4')->first();

        $substance = 'MgSO<sub>4</sub>';
        $target = new Reactant($substance);

        expect($target->substances)->toHaveCount(2)
            ->and($target->substances[0]->element)->toEqual('Mg')
            ->and($target->substances[0]->charge)->toEqual(2)
            ->and($target->substances[0]->polyatomicSubstances)->toHaveCount(0)
            ->and($target->substances[1]->element)->toEqual('SO')
            ->and($target->substances[1]->charge)->toEqual($sulfate->charge)
            ->and($target->substances[1]->polyatomicSubstances)->toHaveCount(2)
            ->and($target->substances[1]->polyatomicSubstances[0]->element)->toEqual('S')
            ->and($target->substances[1]->polyatomicSubstances[0]->atom)->toEqual(1)
            ->and($target->substances[1]->polyatomicSubstances[1]->element)->toEqual('O')
            ->and($target->substances[1]->polyatomicSubstances[1]->atom)->toEqual(4);
    });

    test('will set cation and anion charge correctly for a given reactant', function () {
        $target = new Reactant('FeO');

        expect($target->substances)->toHaveCount(2)
            ->and($target->substances[0]->element)->toEqual('Fe')
            ->and($target->substances[0]->charge)->toEqual(2)
            ->and($target->substances[1]->element)->toEqual('O')
            ->and($target->substances[1]->charge)->toEqual(-2);
    });

    test('assign signed valency magnitudes for NaCl', function () {
        $target = new Reactant('NaCl');

        expect($target->substances[0]->charge)->toEqual(1)
            ->and($target->substances[1]->charge)->toEqual(-1)
            ->and($target->netCharge)->toEqual(0);
    });

    test('assign signed valency magnitudes for 3 HCCl3', function () {
        $target = new Reactant('HCCl<sub>3</sub>');

        expect($target->substances[0]->charge)->toEqual(1)
            ->and($target->substances[1]->charge)->toEqual(2)
            ->and($target->substances[2]->charge)->toEqual(-1)
            ->and($target->netCharge)->toEqual(0);
    });

    test('assign signed valency magnitudes for single atom', function () {
        $target = new Reactant('Na');

        expect($target->substances[0]->charge)->toEqual(1)
            ->and($target->netCharge)->toEqual(1);
    });

    test("assigning proper charges for: ", function ($compound, $firstCharge, $secondCharge, $netCharge) {
       $target = new Reactant($compound);

       expect($target->substances->first()->charge)->toEqual($firstCharge)
           ->and($target->substances->get(1)->charge)->toEqual($secondCharge)
           ->and($target->netCharge)->toEqual($netCharge);
    })->with([
        'HF' => ["HF", 1, -1, 0],
        'HCl' => ["HCl", 1, -1, 0],
        'HBr' => ["HBr", 1, -1, 0],
        'HI' => ["HI", 1, -1, 0],
    ]);
});
