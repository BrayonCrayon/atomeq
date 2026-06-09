<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\BinaryOperator;
use App\ChemicalEvaluator\General\Operand;

class AdditionOperator extends BinaryOperator
{
    public function operate(Operand $left, Operand $right): Operand
    {
        if (! $left instanceof Reactant || ! $right instanceof Reactant) {
            throw new \InvalidArgumentException('Addition operator requires Reactant operands');
        }

        $result = new Reactant();

        $substances = [];

        foreach ($left->substances as $substance) {
            $element = $substance->element;
            if (!isset($substances[$element])) {
                $substances[$element] = clone $substance;
                $substances[$element]->atom *= $left->coefficient;
            } else {
                $substances[$element]->atom += ($substance->atom * $left->coefficient);
            }
        }

        foreach ($right->substances as $substance) {
            $element = $substance->element;
            if (!isset($substances[$element])) {
                $substances[$element] = clone $substance;
                $substances[$element]->atom *= $right->coefficient;
            } else {
                $substances[$element]->atom += ($substance->atom * $right->coefficient);
            }
        }

        ksort($substances);
        $result->substances = array_values($substances);
        $result->coefficient = 1; // Result of addition is a single combined reactant for now

        return $result;
    }
}
