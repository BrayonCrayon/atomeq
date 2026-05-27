<?php

namespace App\ChemicalEvaluator;

use InvalidArgumentException;

class Substance
{
    public int $atom = 1;
    public string $element = '';

    public function __construct($substance)
    {
        if (preg_match('/[a-zA-Z]+/', $substance, $matches)) {
            $this->element = $matches[0];
        } else {
            throw new InvalidArgumentException('Substance must be a valid element.');
        }

        if (preg_match('/<sub>(\d+)<\/sub>/', $substance, $matches)) {
            $this->atom = (int) $matches[1];
        }
    }
}
