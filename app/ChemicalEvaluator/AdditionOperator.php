<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\BinaryOperator;
use App\ChemicalEvaluator\General\Operand;

class AdditionOperator extends BinaryOperator
{
    use ChemicalHelpers;

    public function operate(Operand $left, Operand $right): Operand
    {
        if (! $left instanceof Reactant || ! $right instanceof Reactant) {
            throw new \InvalidArgumentException('Addition operator requires Reactant operands');
        }

        $result = new Reactant();

        $firstValencyOfLeft = $left->substances[0]->valencies->first()->valency;
        $firstValencyOfRight = $right->substances[0]->valencies->first()->valency;

        $substances = [];
        $atomsOfLeft = $this->calculateAtom($firstValencyOfLeft, $firstValencyOfRight);
        $atomsOfRight = $this->calculateAtom($firstValencyOfRight, $firstValencyOfLeft);
        $left->substances[0]->atom = $atomsOfLeft;
        $right->substances[0]->atom = $atomsOfRight;
        $result->substances = [$left->substances[0], $right->substances[0]];
        return $result;
//        foreach ($left->substances as $substance) {
//            $element = $substance->element;
//            if (!isset($substances[$element])) {
//                $substances[$element] = clone $substance;
//                $substances[$element]->atom *= $left->coefficient;
//            } else {
//                $substances[$element]->atom += ($substance->atom * $left->coefficient);
//            }
//        }

//        foreach ($right->substances as $substance) {
//            $element = $substance->element;
//            if (!isset($substances[$element])) {
//                $substances[$element] = clone $substance;
//                $substances[$element]->atom *= $right->coefficient;
//            } else {
//                $substances[$element]->atom += ($substance->atom * $right->coefficient);
//            }
//        }

        $result->substances = array_values($substances);
        $result->coefficient = 1; // Result of addition is a single combined reactant for now

        return $result;
    }
}
