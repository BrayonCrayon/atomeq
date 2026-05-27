<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Reactant extends Operand
{
    // $atom: The number of atoms of a substance. It is the number following the substance ex: (H2O, Na, HCl)
    // $coefficient: The coefficient of a substance. It is the number preceding the substance ex: (2H2O, 2Na, 2HCl)
    // $substance: The symbol of a substance ex: (H2O, Na, HCl)
    // $state: The state of a substance. The letter in brackets following the substance. ex: (2HCl(aq) + 2Na(s) → 2NaCl(aq) + H2(g))
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
