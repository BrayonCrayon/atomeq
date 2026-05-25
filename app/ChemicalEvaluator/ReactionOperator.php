<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\Enums\Reactions;
use App\ChemicalEvaluator\General\BinaryOperator;

class ReactionOperator extends BinaryOperator
{
    public Reactions $type;
}
