<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\Enums\Reactions;
use App\ChemicalEvaluator\General\Operand;
use App\ChemicalEvaluator\General\UnaryOperator;
use App\Models\Element;

class ReactionOperator extends UnaryOperator
{
    public Reactions $type;

    public function __construct(string $reaction)
    {
        $this->type = match ($reaction) {
            '->' => Reactions::NET_FORWARD_REACTION,
            '=' => Reactions::STOICHIOMETRIC_REACTION
        };
    }

    public function operate(Operand $operand): Operand
    {
        if (! $operand instanceof ReactionMixture) {
            return $operand;
        }

        $loneSubstance = $operand->loneElement->substances[0];
        $compoundCation = $operand->compound->substances->first(fn($s) => $s->charge > 0);

        if (! $compoundCation) {
            return $operand->compound;
        }

        [$loneElement, $displacedElement] = Element::query()
            ->whereIn('symbol', [$loneSubstance->element, $compoundCation->element])
            ->get()
            ->keyBy('symbol')
            ->pipe(fn($map) => [$map[$loneSubstance->element], $map[$compoundCation->element]]);

        if (
            $loneElement?->activity_rank === null
            || $displacedElement?->activity_rank === null
            || $loneElement->activity_rank <= $displacedElement->activity_rank
        ) {
            return new NoReactionOperand();
        }

        // Step 8: full displacement logic
        return $operand->compound;
    }
}
