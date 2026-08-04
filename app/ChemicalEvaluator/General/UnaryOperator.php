<?php

namespace App\ChemicalEvaluator\General;

abstract class UnaryOperator extends Operator
{
    abstract public function operate(Operand $operand): Operand;
}
