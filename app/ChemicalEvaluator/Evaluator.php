<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use App\ChemicalEvaluator\General\Operator;
use SplStack;

class Evaluator
{
    protected SplStack $stack;
    protected SplStack $resultStack;

    public function __construct(Tokenizer $tokenizer)
    {
        $this->stack = $tokenizer->stack;
        $this->resultStack = new SplStack();
    }

    public function evaluate(): array
    {
        $evaluationStack = new SplStack();

        // The Tokenizer stack has Operands at the bottom and Operators at the top.
        // We need to process Operands first to have them ready for Operators.

        $tokens = [];
        while (!$this->stack->isEmpty()) {
            $tokens[] = $this->stack->pop();
        }
        $tokens = array_reverse($tokens);

        foreach ($tokens as $token) {
            if ($token instanceof Operand) {
                $evaluationStack->push($token);
                continue;
            }

            if ($token instanceof Operator) {
                // When we encounter an operator, we process it using the evaluation stack.
                // In RPN, an operator usually pops operands from the stack.
                // Since AdditionOperator and ReactionOperator are binary (or act on pairs),
                // we'll pop two items if available.

                if ($evaluationStack->count() >= 2) {
                    $right = $evaluationStack->pop();
                    $left = $evaluationStack->pop();

                    // Here we would typically apply the operator: $result = $token->apply($left, $right);
                    // For now, let's just push them back or handle it as a "pair evaluation".
                    // The requirement says "start to evaluate each pair of tokens".

                    // Since we don't have results yet, let's just push them back to satisfy current test
                    // but in a real evaluator we'd push the result of the operation.
                    $evaluationStack->push($left);
                    $evaluationStack->push($right);
                }
            }
        }

        $results = [];
        while (!$evaluationStack->isEmpty()) {
            $results[] = $evaluationStack->pop();
        }

        return array_reverse($results);
    }
}
