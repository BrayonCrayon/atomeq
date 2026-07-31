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
//        if (! $left instanceof Reactant || ! $right instanceof Reactant || ! $left instanceof ReactionMixture || ! $right instanceof ReactionMixture) {
//            throw new \InvalidArgumentException('Addition operator requires Reactant operands');
//        }

        $leftIsCompound = $left->substances->count() > 1;
        $rightIsCompound = $right->substances->count() > 1;

//        if ($leftIsCompound || $rightIsCompound) {
//            $loneElement = $leftIsCompound ? $right : $left;
//            $compound = $leftIsCompound ? $left : $right;
//            return new ReactionMixture($loneElement, $compound);
//        }

        $result = new Reactant();

        $leftSubstance = $left->substances[0];
        $rightSubstance = $right->substances[0];

        $elements = Element::query()
            ->whereIn('symbol', [$leftSubstance->element, $rightSubstance->element])
            ->get()
            ->keyBy('symbol');

        if (($elements[$leftSubstance->element]->electronegativity ?? 0) > ($elements[$rightSubstance->element]->electronegativity ?? 0)) {
            [$leftSubstance, $rightSubstance] = [$rightSubstance, $leftSubstance];
        }

        /** @var Substance $leftSubstance */
        /** @var Substance $rightSubstance */
        $firstValencyOfLeft = $leftSubstance->getSafeValencies()->where('is_default', true)->first();
        $firstValencyOfRight = $rightSubstance->getSafeValencies()->where('is_default', true)->first();

        $substances = [];
        $leftSubstance->atom = $this->calculateAtom($firstValencyOfLeft->valency, $firstValencyOfRight->valency);
        $rightSubstance->atom = $this->calculateAtom($firstValencyOfRight->valency, $firstValencyOfLeft->valency);

        if($leftSubstance->element === $rightSubstance->element){
            $newSubstance = new Substance($leftSubstance->element);
            $newSubstance->atom = $leftSubstance->atom + $rightSubstance->atom;

            $result->substances = collect([$newSubstance]);
            return $result;
        }


        $result->substances = collect([$leftSubstance, $rightSubstance]);
        $result->coefficient = $left->coefficient;

        return $result;
    }
}
