<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\Enums\Reactions;
use App\ChemicalEvaluator\General\BinaryOperator;

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
}
