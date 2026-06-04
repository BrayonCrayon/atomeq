<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Reactant extends Operand
{
    // $coefficient: The coefficient of a substance. How many molecules it has, and is the number preceding the substance ex: (2H2O, 2Na, 2HCl)
    // $substances: The amount of substance(s) containing their atoms and molecules ex: (H2O, Na, HCl)
    public int $coefficient = 1;
    public array $substances = [];

    const SUBSTANCE_REGEX = '/[A-Z][a-z]?(?:<sub>[0-9]+<\/sub>)?/';

    public function __construct(string $reactant)
    {
        $this->parseReactant($reactant);
    }

    public function parseReactant(string $reactant): void
    {
        if (preg_match('/^\d+/', $reactant, $matches)) {
            $this->coefficient = (int) $matches[0];
        }

        $substancesStr = Str::after($reactant, $this->coefficient);
        if (! $substancesStr) {
            throw new InvalidArgumentException('Reactant must contain at least one substance');
        }

        $leftOvers = preg_replace(self::SUBSTANCE_REGEX, '', $substancesStr);

        if ($leftOvers) {
            throw new InvalidArgumentException('Reactant must contain only substance symbols');
        }

        preg_match_all(self::SUBSTANCE_REGEX, $reactant, $matches);
        collect(...$matches)->each(function ($match) {
            $this->substances[] = new Substance($match);
        });
    }
}
