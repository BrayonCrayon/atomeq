<?php

namespace App\ChemicalEvaluator;

class Bond
{
    public function __construct(public string $bondedElement,
                                public string $centralElement,
                                public int $order = 1,
                                public int $storedElectrons = 2) {}
}
