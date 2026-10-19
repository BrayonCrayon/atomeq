<?php

namespace App\ChemicalEvaluator;

use Illuminate\Contracts\Support\Arrayable;

class Bond implements Arrayable
{
    public function __construct(public BondedElement $leftElement,
                                public BondedElement $rightElement,
                                public int    $level = 1) {
        $leftElement->connections->push($this);
        $rightElement->connections->push($this);
    }

    public function toArray(): array
    {
        return [
            'leftElement' => $this->leftElement->toArray(),
            'rightElement' => $this->rightElement->toArray(),
            'level' => $this->level
        ];
    }
}
