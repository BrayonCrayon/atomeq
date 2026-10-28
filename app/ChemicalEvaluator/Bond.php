<?php

namespace App\ChemicalEvaluator;

use Illuminate\Contracts\Support\Arrayable;

class Bond implements Arrayable
{
    public function __construct(public BondedElement $outsideElement,
                                public BondedElement $insideElement,
                                public int           $level = 1) {
        $outsideElement->connections->push($this);
        $insideElement->connections->push($this);
    }

    public function toArray(): array
    {
        return [
            'leftElement' => $this->outsideElement->toArray(),
            'rightElement' => $this->insideElement->toArray(),
            'level' => $this->level
        ];
    }
}
