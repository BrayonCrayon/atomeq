<?php

namespace App\ChemicalEvaluator;

class Bond
{
    public function __construct(public BondedElement $leftElement,
                                public BondedElement $rightElement,
                                public int    $level = 1) {
        $leftElement->connections->push($this);
        $rightElement->connections->push($this);
    }
}
