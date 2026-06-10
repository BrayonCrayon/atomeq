<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use App\ChemicalEvaluator\General\Operator;
use App\ChemicalEvaluator\General\Token;
use InvalidArgumentException;
use SplStack;

class Tokenizer
{
    public array $tokens = [];
    public SplStack $stack;

    private array $operators = [
        '+' => AdditionOperator::class,
        '=' => ReactionOperator::class
    ];

    private array $operatorPrecedence = [
        ReactionOperator::class => 1,
        AdditionOperator::class => 2,
    ];

    public function __construct()
    {
        $this->stack = new SplStack();
    }

    public function tokenize(string $equation): void
    {
        $equationParts = collect(explode(' ', $equation))->filter();

        if ($equationParts->isEmpty()) {
            throw new InvalidArgumentException('Equation cannot be empty');
        }

        foreach ($equationParts as $part) {
            if (isset($this->operators[$part])) {
                $this->tokens[] = new $this->operators[$part]($part);
                continue;
            }

            $this->tokens[] = new Reactant($part);
        }
    }

    public function higherOrEqualPrecedence(Token $left, Token $right): bool
    {
        return $this->operatorPrecedence[get_class($left)] >= $this->operatorPrecedence[get_class($right)];
    }

    public function organize(): void
    {
        $operatorStack = new SplStack();
        foreach ($this->tokens as $token) {
            if ($token instanceof Operator) {

                while (!$operatorStack->isEmpty() && ($this->higherOrEqualPrecedence($operatorStack->top(), $token))) {
                    $this->stack->push($operatorStack->pop());
                }

                $operatorStack->push($token);
                continue;
            }

            if ($token instanceof Operand) {
                $this->stack->push($token);
            }
        }

        foreach ($operatorStack as $operator) {
            $this->stack->push($operator);
        }
    }
}
