<?php

namespace App\ChemicalEvaluator\General;

abstract class BinaryOperator extends Operator
{
    abstract public function operate(Operand $left, Operand $right): Operand;
}
