<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;

class NoReactionOperand extends Operand
{
    public function __toString(): string
    {
        return 'No Reaction';
    }
}
