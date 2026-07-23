<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use App\Models\PolyatomicIon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Reactant extends Operand
{
    // $coefficient: The coefficient of a substance. How many molecules it has, and is the number preceding the substance ex: (2H2O, 2Na, 2HCl)
    // $substances: The amount of substance(s) containing their atoms and molecules ex: (H2O, Na, HCl)
    public int $coefficient = 1;
    public array $substances = [];

    const SUBSTANCE_REGEX = '/[A-Z][a-z]?(?:<sub>[0-9]+<\/sub>)?/';

    public function __construct(string $reactant = null)
    {
        if ($reactant) {
            $this->parseReactant($reactant);
        }
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

        $isMalformed = preg_replace(self::SUBSTANCE_REGEX, '', $substancesStr);
        if ($isMalformed) {
            throw new InvalidArgumentException('Reactant must contain only substance symbols');
        }

        $polyatomicPattern = PolyatomicIon::query()->get()
            ->map(fn(PolyatomicIon $ion) => Str::replaceMatches('/(?<=[A-Za-z)])(\d+)/', '<sub>$1<\/sub>', $ion->symbol))
            ->join('|');

        $parts = preg_split("/($polyatomicPattern)/", $substancesStr, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($parts as $part) {
            if (preg_match("/^(?:$polyatomicPattern)$/", $part)) {
                $this->substances[] = new Substance($part, true);
            } else {
                preg_match_all(self::SUBSTANCE_REGEX, $part, $matches);
                foreach ($matches[0] as $match) {
                    $this->substances[] = new Substance($match);
                }
            }
        }
    }

    public function __toString(): string
    {
        $string = $this->coefficient > 1 ? (string) $this->coefficient : '';
        foreach ($this->substances as $substance) {
            $string .= $substance;
        }
        return $string;
    }
}
