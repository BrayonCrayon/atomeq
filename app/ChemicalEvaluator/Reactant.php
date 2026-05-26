<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Reactant extends Operand
{
    // $atom: The number of atoms of a substance. It is the number following the substance ex: (H2O, Na, HCl)
    // $coefficient: The coefficient of a substance. It is the number preceding the substance ex: (2H2O, 2Na, 2HCl)
    // $substance: The symbol of a substance ex: (H2O, Na, HCl)
    // $state: The state of a substance. The letter in brackets following the substance. ex: (2HCl(aq) + 2Na(s) → 2NaCl(aq) + H2(g))
    public int $coefficient = 1;
    public int $atom = 1;
    public string $substance = '';

    public function __construct(string $reactant)
    {
        $this->parseReactant($reactant);
    }

    public function parseReactant(string $reactant): void
    {
        if (preg_match('/^\d+/', $reactant, $matches)) {
            $this->coefficient = (int) $matches[0];
        }

        if (preg_match('/[a-zA-Z]+/', $reactant, $matches)) {
            $this->substance = $matches[0];
        } else {
            throw new InvalidArgumentException('Reactant must contain a substance');
        }

        if (preg_match('/<sub>(\d+)<\/sub>/', $reactant, $matches)) {
            $this->atom = (int) $matches[1];
        }
    }
}
