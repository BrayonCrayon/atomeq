<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\BinaryOperator;
use App\ChemicalEvaluator\General\Operand;
use App\ChemicalEvaluator\General\Operator;
use SplStack;

class Evaluator
{
    protected Tokenizer $tokenizer;
    protected SplStack $resultStack;

    public function __construct(Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
        $this->resultStack = new SplStack();
    }

    public function evaluate(): array
    {
        $evaluationStack = new SplStack();

        for ($i = $this->tokenizer->stack->count() - 1; $i >= 0; $i--) {
            $token = $this->tokenizer->stack[$i];

            if ($token instanceof Operand) {
                $evaluationStack->push($token);
                continue;
            }

            if ($token instanceof Operator) {
                if ($token instanceof BinaryOperator && $evaluationStack->count() >= 2) {
                    $right = $evaluationStack->pop();
                    $left = $evaluationStack->pop();

                    $evaluationStack->push($token->operate($left, $right));
                } elseif ($token instanceof ReactionOperator && $evaluationStack->count() === 1) {
                    // Trailing reaction operator with one operand
                    // We just keep the operand as the result
                }
                continue;
            }
        }

        $results = [];
        for ($i = 0; $i < $evaluationStack->count(); $i++) {
            $results[] = $evaluationStack[$i];
        }

        return $results;
    }
}
