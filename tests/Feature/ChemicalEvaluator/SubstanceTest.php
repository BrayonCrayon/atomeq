<?php

use App\ChemicalEvaluator\Substance;
use App\Models\Element;
use App\Models\PolyatomicIon;

describe('Substance', function () {

    test('will retrieve and set charge for elements', function () {
        $substance = new Substance("O<sub>2</sub>");

        expect($substance->atom)->toEqual(2)
            ->and($substance->charge)->toEqual(null)
            ->and($substance->element)->toEqual('O')
            ->and($substance->valencies)->toHaveCount(1)
            ->and($substance->valencies->first()->valency)->toEqual(2)
            ->and($substance->polyatomicSubstances)->toBeEmpty();
    });

    test('will set charge for a polyatomic ion element', function () {
        $sulfate = PolyatomicIon::whereSymbol('SO4')->first();

        $substance = 'SO<sub>4</sub>';
        $target = new Substance($substance, true);

        expect($target->atom)->toBeNull()
            ->and($target->charge)->toEqual($sulfate->charge)
            ->and($target->element)->toEqual('SO')
            ->and($target->valencies)->toBeEmpty()
            ->and($target->polyatomicSubstances)->toHaveCount(2)
            ->and($target->polyatomicSubstances->first()->element)->toEqual('S')
            ->and($target->polyatomicSubstances->first()->atom)->toEqual(1)
            ->and($target->polyatomicSubstances->first()->charge)->toBeNull()
            ->and($target->polyatomicSubstances->last()->element)->toEqual('O')
            ->and($target->polyatomicSubstances->last()->atom)->toEqual(4)
            ->and($target->polyatomicSubstances->last()->charge)->toBeNull();
    });

});
