<?php

namespace App\ChemicalEvaluator;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class BondedElement implements Arrayable
{
    public Collection $connections;
    public int $formalCharge = 0;
    public int $loneElectrons {
        get => $this->lonePairs * 2;
        set(int $value) {}
    }
    public int $bondedPairs {
        get => $this->connections->sum('level');
        set(int $value) {}
    }

    public int $totalElectrons {
        get => $this->loneElectrons + ($this->bondedPairs * 2);
        set(int $value) {}
    }

    public function __construct(public string $element, public int $lonePairs = 0)
    {
        $this->connections = collect();
    }

    public function toArray(): array
    {
        return [
            "formalCharge" => $this->formalCharge,
            "lonePairs" => $this->lonePairs,
            "bondPairs" => $this->bondedPairs,
            "element" => $this->element
        ];
    }
}
