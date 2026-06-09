<?php

namespace App\ChemicalEvaluator;

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

        for ($i = 0; $i < $this->tokenizer->stack->count(); $i++) {
            $token = $this->tokenizer->stack[$i];

            if ($token instanceof Operand) {
                $evaluationStack->push($token);
                continue;
            }

            if ($token instanceof Operator && $evaluationStack->count() >= 2) {
                $right = $evaluationStack->pop();
                $left = $evaluationStack->pop();

                $evaluationStack->push($left);
                $evaluationStack->push($right);
            }
        }

        $results = [];
        for ($i = 0; $i < $evaluationStack->count(); $i++) {
            $results[] = $evaluationStack[$i];
        }

        return $results;
    }
}
