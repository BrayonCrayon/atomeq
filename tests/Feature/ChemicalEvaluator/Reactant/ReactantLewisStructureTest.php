<?php

use App\ChemicalEvaluator\Bond;
use App\ChemicalEvaluator\BondedElement;
use App\ChemicalEvaluator\Reactant;

describe("ReactantLewisStructure", function () {

    describe("PBr₃S", function () {
        test('structure will have correct valence electron count', function () {
            $target = new Reactant('PBr<sub>3</sub>S');

            $lewisHamilton = $target->lewisStructure();

            expect($target->calculatedStructures->first()->totalValenceElectrons)->toEqual(32);
            expect($lewisHamilton)->not()->toBeNull();
        });

        test('structure will remove valence electron count when ion charge is positive', function () {
            $target = new Reactant('PBr<sub>3</sub>S<sup>+</sup>');

            $lewisHamilton = $target->lewisStructure();

            expect($target->calculatedStructures->first()->totalValenceElectrons)->toEqual(31);
            expect($lewisHamilton)->not()->toBeNull();
        });

        test('structure will remove valence electron count when ion charge is negative', function () {
            $target = new Reactant('PBr<sub>3</sub>S<sup>-</sup>');

            $lewisHamilton = $target->lewisStructure();

            expect($target->calculatedStructures->first()->totalValenceElectrons)->toEqual(33);
            expect($lewisHamilton)->not()->toBeNull();
        });

        test('structure will choose a starting central atom', function () {
            $target = new Reactant('PBr<sub>3</sub>S');

            $target->lewisStructure();

            expect($target->calculatedStructures->first()->centralAtom->element)->toEqual('P');
        });

        test('will connect every Br and S to P', function () {
            $target = new Reactant('PBr<sub>3</sub>S');

            $target->lewisStructure();

            $output = $target->calculatedStructures->first()->connectivity->map(function ($connection) {
                return [
                    $connection[0]->element,
                    $connection[1]->element,
                ];
            })->toArray();

            expect($output)->toEqual([
                ['S', 'P'],
                ['Br', 'P'],
                ['Br', 'P'],
                ['Br', 'P'],
            ]);
        });

        test('will create and upgrade bond pairings for PBr₃S', function () {
            $bromines = [
                new BondedElement('Br', 3),
                new BondedElement('Br', 3),
                new BondedElement('Br', 3),
            ];
            $sulfur = new BondedElement('S', 2);
            $phosphorous = new BondedElement('P');

            $expectedBonds = collect([
                new Bond($sulfur, $phosphorous, 2),
                new Bond($bromines[0], $phosphorous),
                new Bond($bromines[1], $phosphorous),
                new Bond($bromines[2], $phosphorous),
            ]);
            $target = new Reactant('PBr<sub>3</sub>S');

            $target->lewisStructure();

            $bonds = $target->calculatedStructures->first()->bonds;

            expect($bonds)->toHaveCount(4)
            ->and($bonds->sortBy([['level','asc']])->values()->toArray())
            ->toEqual($expectedBonds->sortBy([['level','asc']])->values()->toArray());
        });

        test('will subtract 2 electrons for every bond and provide electrons from Octet rule to bonds', function () {
            $target = new Reactant('PBr<sub>3</sub>S');

            $target->lewisStructure();

            $total = 32;
            $electronOctetCount = ($target->calculatedStructures->first()->bonds->count() * 8);
            expect($target->calculatedStructures->first()->centralAtom->loneElectrons)->toEqual($total - $electronOctetCount);
        });
    });

    describe("SO₂", function () {
        test('will calculate formal charges and upgrade bonds for oxygen elements', function () {
            $expectedFormalCharges = collect([
                'O' => 0,
                'S' => 0,
            ]);
            $expectedBondLevel = ['S' => 1, 'O' => 2];
            $target = new Reactant('SO<sub>2</sub>');

            $target->lewisStructure();

            $calculatedStructure = $target->calculatedStructures->first();

            $calculatedStructure->bondedElements->each(function (BondedElement $item) use ($expectedFormalCharges) {
                expect($item->formalCharge)->toEqual($expectedFormalCharges[$item->element]);
            });

            $calculatedStructure->bonds->each(function (Bond $bond) use ($expectedBondLevel) {
                expect($bond->level)->toEqual($expectedBondLevel[$bond->outsideElement->element]);
            });
        });

        test("will ensure and rollback any bad calculated structures when upgrading", function () {
            $target = new Reactant('SO<sub>2</sub>');

            $target->lewisStructure();

            expect($target->calculatedStructures)->toHaveCount(2);
        });
    });

    describe("H2O", function () {
        test('structure will have correct valence electron count', function () {
            $target = new Reactant('H<sub>2</sub>O');

            $target->lewisStructure();

            expect($target->calculatedStructures->first()->totalValenceElectrons)->toEqual(8);
        });

        test('will connect both H to O', function () {
            $target = new Reactant('H<sub>2</sub>O');

            $target->lewisStructure();

            $output = $target->calculatedStructures->first()->connectivity->map(function ($connection) {
                return [
                    $connection[0]->element,
                    $connection[1]->element,
                ];
            })->toArray();

            expect($output)->toEqual([
                ['H', 'O'],
                ['H', 'O'],
            ]);
        });

        test('structure will choose a starting central atom ignoring H', function () {
            $target = new Reactant('H<sub>2</sub>O');

            $target->lewisStructure();

            expect($target->calculatedStructures->first()->centralAtom->element)->toEqual('O');
        });

        test('will put remaining electrons on the central atom', function () {
            $target = new Reactant('H<sub>2</sub>O');

            $target->lewisStructure();

            $total = 8;
            $electronOctetCount = ($target->calculatedStructures->first()->bonds->count() * 2);
            expect($target->calculatedStructures->first()->centralAtom->loneElectrons)->toEqual($total - $electronOctetCount);
        });

        test('will calculate formal charges', function () {

            $expectedFormalCharges = collect([
                'H' => 0,
                'O' => 0,
            ]);
            $target = new Reactant('H<sub>2</sub>O');

            $target->lewisStructure();

            $calculatedStructure = $target->calculatedStructures->first();

            $calculatedStructure->bondedElements->each(function (BondedElement $item) use ($expectedFormalCharges) {
                expect($item->formalCharge)->toEqual($expectedFormalCharges[$item->element]);
            });
        });
    });

    describe("H2SO4", function () {
        test('will connect O to S and H to O', function () {
            $target = new Reactant('H<sub>2</sub>SO<sub>4</sub>');

            $target->lewisStructure();

            $output = $target->calculatedStructures->first()->connectivity->map(function ($connection) {
                return [
                    $connection[0]->element,
                    $connection[1]->element,
                ];
            })->toArray();

            expect($output)->toEqual([
                ['O', 'S'],
                ['O', 'S'],
                ['O', 'S'],
                ['O', 'S'],
                ['H', 'O'],
                ['H', 'O'],
            ]);
        });

        test('structure will have correct bonds', function () {
            $oxygens = [
                new BondedElement('O', 2),
                new BondedElement('O', 2),
                new BondedElement('O', 2),
                new BondedElement('O', 2)
            ];
            $sulfur = new BondedElement('S');
            $hydrogens = [new BondedElement('H'), new BondedElement('H')];


            $expectedBonds = collect([
                new Bond($oxygens[0], $sulfur, 2),
                new Bond($oxygens[1], $sulfur, 2),
                new Bond($oxygens[2], $sulfur, 1),
                new Bond($oxygens[3], $sulfur, 1),
                new Bond($hydrogens[0], $oxygens[2], 1),
                new Bond($hydrogens[1], $oxygens[3], 1),
            ]);

            $target = new Reactant('H<sub>2</sub>SO<sub>4</sub>');

            $target->lewisStructure();

            $centralAtomBonds = $target->calculatedStructures->first()->bonds;
            expect($centralAtomBonds->count())->toEqual(6)
                ->and($centralAtomBonds->sortBy([['level','asc']])->values()->toArray())
                ->toEqual($expectedBonds->sortBy([['level','asc']])->values()->toArray());
        });
    });

    describe('NH₄⁺', function(){

        test('ammonium ion', function(){
            $hydrogens = collect([
                new BondedElement('H'),
                new BondedElement('H'),
                new BondedElement('H'),
                new BondedElement('H'),
            ]);
            $nitrogen = new BondedElement('N');
            $nitrogen->formalCharge = 1;
            $expectedBonds = $hydrogens->map(fn (BondedElement $outerElement) => new Bond($outerElement, $nitrogen));
            $target = new Reactant('NH<sub>4</sub><sup>+</sup>');

            $target->lewisStructure();

            $result = $target->calculatedStructures->first();
            expect($result->bonds->count())->toEqual(4)
                ->and($result->formalChargeScore)->toEqual(1)
                ->and($result->bonds->sortBy([['level','asc']])->values()->toArray())->toEqual($expectedBonds->sortBy([['level','asc']])->values()->toArray());
        });
    });

    describe('O₃', function () {
       test('final form is 1', function () {
           $target = new Reactant('O<sub>3</sub>');

           $target->lewisStructure();

           $calculatedStructure = $target->calculatedStructures->first();

           expect($calculatedStructure->formalChargeScore)->toBe(2);
       });
    });

    describe('NO2', function () {
        test('nitrogen dioxide', function () {
            $oxygens = collect([
                new BondedElement('O', 2),
                new BondedElement('O', 2),
            ]);
            $nitrogen = new BondedElement('N');
            $nitrogen->formalCharge = 1;
            $expectedBonds = $oxygens->map(fn (BondedElement $outerElement) => new Bond($outerElement, $nitrogen, 2));
            $target = new Reactant('NO<sub>2</sub>');

            $target->lewisStructure();

            $result = $target->calculatedStructures->first();
            expect($result->bonds->count())->toEqual(2)
                ->and($result->formalChargeScore)->toEqual(1)
                ->and($result->bonds->sortBy([['level','asc']])->values()->toArray())->toEqual($expectedBonds->sortBy([['level','asc']])->values()->toArray());
        });
    });

    describe("H₂", function () {
       test("hydrogen dioxide", function () {
           $hydrogens = collect([
               new BondedElement('H'),
               new BondedElement('H'),
           ]);
           $bond = new Bond($hydrogens[0], $hydrogens[1]);
           $target = new Reactant('H<sub>2</sub>');

           $target->lewisStructure();

           $result = $target->calculatedStructures->first();
           expect($result->bonds->count())->toEqual(1)
               ->and($result->formalChargeScore)->toEqual(0)
               ->and($result->bonds->toArray())->toEqual(collect([$bond])->toArray());
       });
    });
});
