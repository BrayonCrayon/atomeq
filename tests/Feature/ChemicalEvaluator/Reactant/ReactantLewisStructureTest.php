<?php

use App\ChemicalEvaluator\Bond;
use App\ChemicalEvaluator\Reactant;

describe("ReactantLewisStructure", function() {

    describe("PBr₃S", function() {
        test('structure will have correct valence electron count', function() {
            $target = new Reactant('PBr<sub>3</sub>S');

            $lewisHamilton = $target->lewisStructure();

            expect($target->lewis->totalValenceElectrons)->toEqual(32);
            expect($lewisHamilton)->not()->toBeNull();
        });

        test('structure will remove valence electron count when ion charge is positive', function() {
            $target = new Reactant('PBr<sub>3</sub>S<sup>+</sup>');

            $lewisHamilton = $target->lewisStructure();

            expect($target->lewis->totalValenceElectrons)->toEqual(31);
            expect($lewisHamilton)->not()->toBeNull();
        });

        test('structure will remove valence electron count when ion charge is negative', function() {
            $target = new Reactant('PBr<sub>3</sub>S<sup>-</sup>');

            $lewisHamilton = $target->lewisStructure();

            expect($target->lewis->totalValenceElectrons)->toEqual(33);
            expect($lewisHamilton)->not()->toBeNull();
        });

        test('structure will choose a starting central atom', function() {
            $target = new Reactant('PBr<sub>3</sub>S');

            $target->lewisStructure();

            expect($target->lewis->centralAtom)->toEqual('P');
        });

        test('will create bond pairings for PBr₃S', function () {
            $expectedBonds = collect([
                ['central' => 'P', 'outer' => 'Br'],
                ['central' => 'P', 'outer' => 'Br'],
                ['central' => 'P', 'outer' => 'Br'],
                ['central' => 'P', 'outer' => 'S'],
            ]);
            $target = new Reactant('PBr<sub>3</sub>S');

            $target->lewisStructure();

            expect($target->lewis->bonds)->toHaveCount(4);

            $mappedBonds = $target->lewis->bonds->map(function(Bond $bond) {
                return [
                    'central' => $bond->centralElement,
                    'outer' => $bond->bondedElement
                ];
            });

            expect($mappedBonds)->toEqual($expectedBonds);
        });

        /**
         * For context reference see the nonmetal_oxidation_state_engine_design.md document
         *
         * This test accounts for steps 6, 7;
         * - Removing 2 electrons on remainingValenceElectrons for every bond pair.
         * - For every atom bond; we'll need to re-fill the atom up to 8. We remove the calculated refill from remainingValenceElectrons
         */
        test('will subtract 2 electrons for every bond and provide electrons from Octet rule to bonds', function () {
            $target = new Reactant('PBr<sub>3</sub>S');

            $target->lewisStructure();

            $total = 32;
            $electronOctetCount = ($target->lewis->bonds->count() * 8);
            expect($target->lewis->remainingValenceElectrons)->toEqual($total - $electronOctetCount);
        });
    });

    describe("H2O", function() {
        test('structure will have correct valence electron count', function() {
            $target = new Reactant('H<sub>2</sub>O');

            $target->lewisStructure();

            expect($target->lewis->totalValenceElectrons)->toEqual(8);
        });

        test('structure will choose a starting central atom ignoring H', function() {
            $target = new Reactant('H<sub>2</sub>O');

            $target->lewisStructure();

            expect($target->lewis->centralAtom)->toEqual('O');
        });

        /**
         * For context reference see the nonmetal_oxidation_state_engine_design.md document
         *
         * This test accounts for steps 6, 7;
         * - Removing 2 electrons on remainingValenceElectrons for every bond pair.
         * - For every atom bond; we'll need to re-fill the atom up to 8. We remove the calculated refill from remainingValenceElectrons
         * - SPECIAL CASE: Hydrogen only gets 2
         */
        test('will subtract 2 electrons for every bond and provide electrons from Octet rule to bonds', function () {
            $target = new Reactant('H<sub>2</sub>O');

            $target->lewisStructure();

            $total = 8;
            $electronOctetCount = ($target->lewis->bonds->count() * 2);
            expect($target->lewis->remainingValenceElectrons)->toEqual($total - $electronOctetCount);
        });
    });
});
