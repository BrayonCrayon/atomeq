<?php

namespace App\ChemicalEvaluator;

use InvalidArgumentException;

class Substance
{
    use ChemicalHelpers;
    // $atom: The number of atoms of a substance. It is the number following the substance ex: (H2, Na3, Cl7)
    // $element: The name of the substance ex: (H, Na, Cl)
    public int $atom = 1;
    public string $element = '';
    public int|null $valency = null;

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

        $this->valency = $this->valencyLookup($this->element);
    }

    public function __toString(): string
    {
        $string = $this->element;
        if ($this->atom > 1) {
            $string .= "<sub>{$this->atom}</sub>";
        }
        return $string;
    }
}
