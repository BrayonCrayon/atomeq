<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\BinaryOperator;
use App\ChemicalEvaluator\General\Operand;
use App\Models\Element;

class AdditionOperator extends BinaryOperator
{
    use ChemicalHelpers;

    public function operate(Operand $left, Operand $right): Operand
    {
        if (! $left instanceof Reactant || ! $right instanceof Reactant) {
            throw new \InvalidArgumentException('Addition operator requires Reactant operands');
        }

        //        $leftIsCompound = $left->substances->count() > 1;
        //        $rightIsCompound = $right->substances->count() > 1;


        $result = new Reactant();

        $elements = Element::query()
            ->whereIn('symbol', [
                ...$left->substances->pluck('element'),
                ...$right->substances->pluck('element')
            ])
            ->get()
            ->keyBy('symbol');

        $allSubstances = collect([...$left->substances , ...$right->substances])
            ->sortBy(function (Substance $sub) use ($elements) {
                return $elements[$sub->element]->electronegativity ?? 0;
            });

//        /** @var Substance $leftSubstance */
//        /** @var Substance $rightSubstance */
//        $firstValencyOfLeft = $leftSubstance->getSafeValencies()->where('is_default', true)->first();
//        $firstValencyOfRight = $rightSubstance->getSafeValencies()->where('is_default', true)->first();
//
//        $substances = [];
//        $leftSubstance->atom = $this->calculateAtom($firstValencyOfLeft->valency, $firstValencyOfRight->valency);
//        $rightSubstance->atom = $this->calculateAtom($firstValencyOfRight->valency, $firstValencyOfLeft->valency);
//
//        if($leftSubstance->element === $rightSubstance->element){
//            $newSubstance = new Substance($leftSubstance->element);
//            $newSubstance->atom = $leftSubstance->atom + $rightSubstance->atom;
//
//            $result->substances = collect([$newSubstance]);
//            return $result;
//        }
//
//
        $result->substances = $allSubstances;
        $result->coefficient = $left->coefficient;

        return $result;
    }
}
