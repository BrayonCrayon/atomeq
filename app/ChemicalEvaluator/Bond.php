<?php

namespace App\ChemicalEvaluator;

class Bond
{
    // TODO: Make a BondedElement class instead of these strings vvvvvv
    public function __construct(public BondedElement $leftElement,
                                public BondedElement $rightElement,
                                public int    $level = 1) {
        $leftElement->connections->push($this);
        $rightElement->connections->push($this);
    }
}
