<?php

namespace App\ChemicalEvaluator;

use Illuminate\Support\Collection;

class BondedElement
{
    public Collection $connections;
    public int $formalCharge = 0;
    public int $loneElectrons {
        get => $this->lonePairs * 2;
    }
    public int $bondedPairs {
        get => $this->connections->sum('level');
    }
    public int $bondedElectrons {
        get => $this->bondedPairs * 2;
    }

    public function __construct(public string $element, public int $lonePairs = 0)
    {
        $this->connections = collect();
    }
}
