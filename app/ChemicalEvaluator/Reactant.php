<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use App\Models\Element;
use App\Models\PolyatomicIon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use App\ChemicalEvaluator\ChemicalHelpers;

class Reactant extends Operand
{
    use ChemicalHelpers;
    // $coefficient: The coefficient of a substance. How many molecules it has, and is the number preceding the substance ex: (2H2O, 2Na, 2HCl)
    // $substances: The amount of substance(s) containing their atoms and molecules ex: (H2O, Na, HCl)
    public int $coefficient = 1;
    /** @var Collection<int, Substance> */
    public Collection $substances;

    public int $netCharge = 0;

    const SUBSTANCE_REGEX = '/[A-Z][a-z]?(?:<sub>[0-9]+<\/sub>)?/';

    public function __construct(string $reactant = null)
    {
        $this->substances = collect();
        if ($reactant) {
            $this->parseReactant($reactant);
        }

        $this->assignCharges();
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

        $substanceParts = preg_split("/($polyatomicPattern)/", $substancesStr, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($substanceParts as $substance) {
            if (preg_match("/^(?:$polyatomicPattern)$/", $substance)) {
                $this->substances->push(new Substance($substance, true));
            } else {
                preg_match_all(self::SUBSTANCE_REGEX, $substance, $substanceMatches);
                foreach ($substanceMatches[0] as $item) {
                    $this->substances->push(new Substance($item));
                }
            }
        }
    }

    public function assignCharges(): void
    {
        $polyatomicIons = $this->substances->where('isPolyatomic', true);
        $regularElements = $this->substances->where('isPolyatomic', false);

        if ($this->substances->isEmpty() || $this->substances->count() === 1 && $polyatomicIons->count() === 1) {
            return;
        }

        if ($polyatomicIons->count() > 0 && $regularElements->count() > 0) {
            $regularElements->first()->charge = ($polyatomicIons->first()->charge * -1);
            return;
        }

        $regularElements->reduce(function (Substance|null $left, Substance $right) {
            if (is_null($left)) {
                return $right;
            }

            $leftElement = Element::query()->where('symbol', $left->element)->first();
            $rightElement = Element::query()->where('symbol', $right->element)->first();

            $leftElementValency = $this->valencyLookup($leftElement->symbol);
            $rightElementValency = $this->valencyLookup($rightElement->symbol);
            $left->charge = $leftElementValency->first()->valency;
            $left->charge = $leftElement->electronegativity > $rightElement->electronegativity ? $left->charge * -1 : $left->charge;
            $right->charge = $rightElementValency->first()->valency;
            $right->charge = $rightElement->electronegativity > $leftElement->electronegativity ? $right->charge * -1 : $right->charge;


            return $right;
        });
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
