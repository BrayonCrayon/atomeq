<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use App\ChemicalEvaluator\General\Operator;
use InvalidArgumentException;
use SplStack;

class Tokenizer
{
    public array $tokens = [];
    public SplStack $stack;

    private array $operators = [
        '+' => AdditionOperator::class,
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

    public function organize(): void
    {
        $operatorHolder = null;
        foreach ($this->tokens as $token) {

            if (!$operatorHolder && $token instanceof Operator) {
                $operatorHolder = $token;
                continue;
            }

            if ($token instanceof Operand) {
                $this->stack->push($token);
            }
        }

        $this->stack->push($operatorHolder);
    }
}
