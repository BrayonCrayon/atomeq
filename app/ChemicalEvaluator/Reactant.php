<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use App\Models\Element;
use App\Models\PolyatomicIon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Reactant extends Operand
{
    use ChemicalHelpers;

    public const oxidationStates = [
        'H' => [-1, 1],
        'C' => [-4, -3, -2, -1, 0, 1, 2, 3, 4],
        'N' => [-3, -2, -1, 0, 1, 2, 3, 4, 5],
        'O' => [-2, -1, 0, 1, 2],
        'F' => [-1, 0],
        'P' => [-3, -2, -1, 0, 1, 3, 5],
        'S' => [-2, -1, 0, 1, 2, 3, 4, 5, 6],
        'Cl' => [-1, 0, 1, 3, 5, 7],
        'Se' => [-2, 0, 2, 4, 6],
        'Br' => [-1, 0, 1, 3, 5, 7],
        'I' => [-1, 0, 1, 3, 5, 7],
        'At' => [-1, 0, 1, 3, 5, 7],

        // Noble gases
        'He' => [0],
        'Ne' => [0],
        'Ar' => [0],
        'Kr' => [0, 2],
        'Xe' => [0, 2, 4, 6, 8],
        'Rn' => [0, 2],
    ];

    // $coefficient: The coefficient of a substance. How many molecules it has, and is the number preceding the substance ex: (2H2O, 2Na, 2HCl)
    // $substances: The amount of substance(s) containing their atoms and molecules ex: (H2O, Na, HCl)
    public int $coefficient = 1;

    /** @var Collection<int, Substance> */
    public Collection $substances;

    public int $netCharge = 0;
    public LewisService $lewis;
    public const SUBSTANCE_REGEX = '/[A-Z][a-z]?(?:<sub>[0-9]+<\/sub>)?(?:<sup>[0-9]*[+\-]<\/sup>)?/';

    public const NET_NEUTRAL_CHARGE = ['C', 'P'];

    public function __construct(?string $reactant = null)
    {
        $this->lewis = new LewisService();
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
        if (!$substancesStr) {
            throw new InvalidArgumentException('Reactant must contain at least one substance');
        }

        $isMalformed = preg_replace(self::SUBSTANCE_REGEX, '', $substancesStr);
        if ($isMalformed) {
            throw new InvalidArgumentException('Reactant must contain only substance symbols');
        }

        $polyatomicPattern = PolyatomicIon::query()->get()
            ->map(fn (PolyatomicIon $ion) => Str::replaceMatches('/(?<=[A-Za-z)])(\d+)/', '<sub>$1<\/sub>', $ion->symbol))
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
        $nonPolySubstances = $this->substances->where('isPolyatomic', false);

        if ($this->substances->isEmpty() || $this->substances->count() === 1 && $polyatomicIons->count() === 1) {
            return;
        }

        if ($polyatomicIons->count() > 0 && $nonPolySubstances->count() > 0) {
            $nonPolySubstances->first()->charge = ($polyatomicIons->first()->charge * -1);

            return;
        }

        $elements = Element::query()
            ->with('type')
            ->whereIn('symbol', $nonPolySubstances->pluck('element')->toArray())
            ->get();

        $includesMetals = $elements->filter(fn ($el) => $el->metal)->count() > 0;

        $this->calculateCharges($nonPolySubstances, $elements, $includesMetals);

        if (!$includesMetals) {
            $hardSetCharges = collect([
                'F' => -1,
                'H' => 1,
                'O' => -2,
            ]);
            $nonPolySubstances->whereIn('element', $hardSetCharges->keys())
                ->each(function (Substance $substance) use ($hardSetCharges) {
                    $substance->charge = $hardSetCharges[$substance->element];
                });

            $halogens = $elements->where('type.name', 'halogen');
            $nonPolySubstances
                ->filter(fn (Substance $substance) => $halogens->some('symbol', $substance->element))
                ->each(function (Substance $halogenSubstance) {
                    $halogenSubstance->charge = -1;
                });

            $specialCases = $nonPolySubstances->whereIn('element', self::NET_NEUTRAL_CHARGE);

            $this->netCharge = 0;
            $nonPolySubstances->filter(fn ($el) => !in_array($el->element, self::NET_NEUTRAL_CHARGE))
                ->each(function (Substance $sub) {
                    $this->netCharge += $sub->charge * $sub->atom;
                });

            $specialCases->each(function (Substance $sub) {
                $sub->charge = $this->netCharge * -1;
                $this->netCharge = 0;
            });
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

    public function getChargeValency(): int
    {
        if ($this->substances->count() > 1) {

            return $this->substances->reduce(fn ($sub) => $sub?->charge ?? 0);
        }

        return $this->substances->first()->isPolyatomic
            ? $this->substances->first()->charge
            : $this->substances->first()->valencies->first()->valency;
    }

    public function setAtomCount(int $atomCount): void
    {
        if ($this->substances->count() > 1) {

            $this->substances->each(function (Substance $substance) use ($atomCount) {
                $substance->atom *= $atomCount;
            });

            return;
        }

        $this->substances->first()->atom = $atomCount;

    }

    public function getValencyOrOxidationState(string $symbol, bool $valency): int
    {
        if ($valency) {
            return $this->valencyLookup($symbol)->first()->valency;
        }

        return $this->oxidationLookup($symbol);
    }

    public function calculateCharges(Collection $substancesCollection, Collection $elements, bool $includesMetals): void
    {
        $substancesCollection->each(function (Substance $current, int $idx) use ($substancesCollection, $elements, $includesMetals) {

            $next = $substancesCollection[$idx + 1] ?? null;

            if (!$current->charge) {
                $currentElementValency = $this->getValencyOrOxidationState($current->element, $includesMetals);
                $current->charge = $currentElementValency;
            }

            if ($next) {

                $nextElementValency = $this->getValencyOrOxidationState($next->element, $includesMetals);
                $next->charge = $nextElementValency;

                $currentElementElectronegativity = $elements->first(fn ($el) => $el->symbol === $current->element)->electronegativity;
                $nextElementElectronegativity = $elements->first(fn ($el) => $el->symbol === $next->element)->electronegativity;

                if ($currentElementElectronegativity > $nextElementElectronegativity) {
                    $current->charge = $current->charge * -1;
                } elseif ($currentElementElectronegativity < $nextElementElectronegativity) {
                    $next->charge = $next->charge * -1;
                }
            }

            $this->netCharge += $current->charge * $current->atom;
        });
    }

    private function oxidationLookup(string $symbol): int
    {
        return self::oxidationStates[$symbol][0];
    }

    public function lewisStructure(): array
    {
        // Step 2
        // Step 3
        $this->lewis->calculateTotalValenceElectrons($this->substances);

        // step 4
        $this->lewis->assignCentralAtom($this->substances);

        // Step 5 & 6
        $this->lewis->assignDefaultBonds($this->substances);

        // Step 7
        $this->lewis->assignOutsideBondsLonePairs();

        return ['test'];
    }
}
