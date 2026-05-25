<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\Enums\Reactions;
use App\ChemicalEvaluator\General\Operand;

class Reaction extends Operand
{
    public Reactions $type;
}
