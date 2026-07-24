<?php

use App\ChemicalEvaluator\Reactant;
use App\Models\Element;
use App\Models\PolyatomicIon;
use App\Models\Valency;

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
        Element::factory()
            ->hasValencies(1, ['valency' => 2])
            ->create([
                'symbol' => 'O',
                'electronegativity' => 3.44
            ]);
        Element::factory()
            ->hasValencies(1, ['valency' => 1])
            ->create([
                'symbol' => 'H',
                'electronegativity' => 2.2
            ]);

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
        Element::factory()->hasValencies(1, ['valency' => 2])->create([
            'symbol' => 'Mg',
            'electronegativity' => 1.31
        ]);
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
        Element::factory()
            ->has(
                Valency::factory()
                    ->sequence(
                        ['valency' => 2],
                        ['valency' => 3]
                    )
            )
            ->create([
                'symbol' => 'Fe',
                'electronegativity' => 1.83
            ]);
        Element::factory()
            ->hasValencies(1, ['valency' => 2])
            ->create([
                'symbol' => 'O',
                'electronegativity' => 3.44
            ]);

        $target = new Reactant('FeO');

        expect($target->substances)->toHaveCount(2)
            ->and($target->substances[0]->element)->toEqual('Fe')
            ->and($target->substances[0]->charge)->toEqual(1)
            ->and($target->substances[1]->element)->toEqual('O')
            ->and($target->substances[1]->charge)->toEqual(-1);
    });
});
