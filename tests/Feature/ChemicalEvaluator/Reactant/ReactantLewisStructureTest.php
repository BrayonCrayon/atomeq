<?php

use App\ChemicalEvaluator\Reactant;

describe("ReactantLewisStructure", function() {

    test('PBr₃S structure', function() {
        $target = new Reactant('PBr<sub>3</sub>S');

        $lewisHamilton = $target->lewisStructure();

        expect($lewisHamilton)->not()->toBeNull();
    });
});
