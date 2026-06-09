<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\Enums\Reactions;
use App\ChemicalEvaluator\General\BinaryOperator;
use App\ChemicalEvaluator\General\Operand;

class ReactionOperator extends BinaryOperator
{
    public Reactions $type;

    public function __construct(string $reaction)
    {
        $this->type = match ($reaction) {
            '->' => Reactions::NET_FORWARD_REACTION,
            '=' => Reactions::STOICHIOMETRIC_REACTION
        };
    }

    public function operate(Operand $left, Operand $right): Operand
    {
        // For now, we return the right side as the result of the evaluation
        // but we could also perform verification here.
        return $right;
    }
}
