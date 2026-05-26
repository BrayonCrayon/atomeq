<?php

namespace App\ChemicalEvaluator;

use InvalidArgumentException;

class Tokenizer
{
    public array $tokens = [];

    private array $operators = [
        '+' => AdditionOperator::class,
    ];

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
}
