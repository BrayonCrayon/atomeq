<?php

namespace App\ChemicalEvaluator;

class Bond
{
    // TODO: Make a BondedElement class instead of these strings vvvvvv
    public function __construct(public string $bondedElement,
                                public string $centralElement,
                                public int $order = 1,
                                public int $storedElectrons = 2) {}
}
