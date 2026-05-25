<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;

class Reactant extends Operand
{
    // $atom: The number of atoms of a substance. It is the number following the substance ex: (H2O, Na, HCl)
    // $coefficient: The coefficient of a substance. It is the number preceding the substance ex: (2H2O, 2Na, 2HCl)
    // $substance: The symbol of a substance ex: (H2O, Na, HCl)
    // $state: The state of a substance. The letter in brackets following the substance. ex: (2HCl(aq) + 2Na(s) → 2NaCl(aq) + H2(g))
    public function __construct(public string $substance, public int $coefficient = 1, public int $atom = 1) { }
}
