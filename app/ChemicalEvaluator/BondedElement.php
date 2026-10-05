<?php

namespace App\ChemicalEvaluator;

use Illuminate\Support\Collection;

class BondedElement
{
    public Collection $connections;
    public int $formalCharge = 0;
    public function __construct(public string $element, public int $storedElectrons = 2)
    {
        $this->connections = collect();
    }
}
