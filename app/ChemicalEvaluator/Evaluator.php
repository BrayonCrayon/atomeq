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

    public function evaluate(): string
    {
        $evaluationStack = new SplStack();

        for ($i = $this->tokenizer->stack->count() - 1; $i >= 0; $i--) {
            $token = $this->tokenizer->stack[$i];

            if ($token instanceof Operand) {
                $evaluationStack->push($token);
                continue;
            }

            if ($token instanceof Operator) {
                if ($token instanceof ReactionOperator) {
                    if ($evaluationStack->count() >= 2) {
                        $right = $evaluationStack->pop();
                        // For '=' we return the right side if it's there
                        $evaluationStack->push($right);
                    }
                } elseif ($token instanceof BinaryOperator && $evaluationStack->count() >= 2) {
                    $right = $evaluationStack->pop();
                    $left = $evaluationStack->pop();

                    $evaluationStack->push($token->operate($left, $right));
                }
                continue;
            }
        }

        return $evaluationStack->isEmpty() ? '' : (string) $evaluationStack->pop();
    }
}
