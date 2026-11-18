<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\Operand;
use App\Models\Element;
use App\Models\MonatomicCation;
use App\Models\PolyatomicIon;
use DeepCopy\DeepCopy;
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

    /** @var Collection<string, LewisService>  */
    public Collection $calculatedStructures;

    // Global regex used to identify an individual substance.
    //
    // [A-Z] = Exactly one uppercase letter.
    // [a-z]? = Optional lowercase letter (element symbol).
    //
    // (?:<sub>[0-9]+<\/sub>)?
    //     Optional non-capturing group for a subscript.
    //     [0-9]+ requires at least one digit.
    //
    // (?:<sup>[0-9]*[+\-]<\/sup>)?
    //     Optional non-capturing group for an ionic charge.
    //     [0-9]* allows zero or more charge digits.
    //     [+\-] requires either a plus or minus sign.
    //
    // Examples:
    // H
    // Fe
    // H<sub>2</sub>
    // Fe<sup>2+</sup>
    // Cl<sup>-</sup>
    private const SUBSTANCE_REGEX = '/[A-Z][a-z]?(?:<sub>[0-9]+<\/sub>)?(?:<sup>[0-9]*[+\-]<\/sup>)?/';

    private const NET_NEUTRAL_CHARGE = ['C', 'P'];

    public function __construct(?string $reactant = null)
    {
        $this->calculatedStructures = collect();
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

        // A single element with a subscript making up the whole reactant ex: (H2, O2, N2)
        $isomerPattern = '/^[A-Z][a-z]?<sub>[0-9]+<\/sub>$/';
        $isIsomer = (bool) preg_match($isomerPattern, $substancesStr);

        $polyatomicPattern = PolyatomicIon::query()->get()
            ->map(fn (PolyatomicIon $ion) => Str::replaceMatches('/(?<=[A-Za-z)])(\d+)/', '<sub>$1<\/sub>', $ion->symbol) . "<sup>$ion->charge<\/sup>")
            ->reject(fn (string $ion) => $isIsomer && preg_match($isomerPattern, stripslashes($ion)))
            ->join('|');

        // Unique element symbols (Fe, Cu, etc. appear once per charge), longest first so alternation prefers "Hg" over "H"
        $monatomicCations = MonatomicCation::query()->with('element')->get()
            ->map(fn (MonatomicCation $cation) => $cation->element->symbol)
            ->unique()
            ->sortByDesc(fn (string $symbol) => strlen($symbol))
            ->join('|');

        $polyatomicCations = PolyatomicIon::query()->where('is_cation', true)->get()
            ->map(fn (PolyatomicIon $ion) => Str::replaceMatches('/(?<=[A-Za-z)])(\d+)/', '<sub>$1<\/sub>', $ion->symbol))
            ->join('|');

        // A monatomic cation (with optional subscript/charge) leading the reactant, immediately followed by a polyatomic ion ex: (Na<sub>2</sub>SO<sub>4</sub>, KNO<sub>3</sub>)
        $cationBeforePolyatomicPattern = "/^(?:$monatomicCations)(?![a-z])(?:<sub>[0-9]+<\/sub>)?(?:<sup>[0-9]*[+\-]<\/sup>)?(?:$polyatomicPattern)/";
        $hasCationBeforePolyatomic = $monatomicCations !== ''
            && $polyatomicPattern !== ''
            && (bool) preg_match($cationBeforePolyatomicPattern, $substancesStr);

        if ($hasCationBeforePolyatomic)
        {
            $this->substances->push(new Substance($substancesStr, true));
            return;
        }

        // TODO: Psudeocode for another early return for another polyatomic case.
        //  check if there is polyatomic at all
        //  check polyatomicCation + polyatomicIon
        //  if ENTIRE THING matches any polyatomicIon

        // update regex to include sup and need to include the charge when matching with polyatomic elements

        if (preg_match("/^(?:$polyatomicPattern)$/", $substancesStr) && preg_match("/^(?:$polyatomicCations)$/", $substancesStr) ) {
            $this->substances->push(new Substance($substancesStr, true));
            return;
        }

        // TODO: drews idea:
        //  right now i think the issue is that if polyatomic is inside of our reactant then it has issues
        //  Past this point the entire thing needs to match the polyatomic ion


        // We know for sure at this point we aren't dealing with any silly polyatomic stuffs, so normal!
        preg_match_all(self::SUBSTANCE_REGEX, $substancesStr, $substanceMatches);
        foreach ($substanceMatches[0] as $item) {
            $sub = new Substance($item);
            $sub->isIsomer = $isIsomer;
            $this->substances->push($sub);
        }
    }


    public function parseReactantExplained(string $reactant): void
    {
        // Look for a coefficient at the very beginning of the reactant.
        //
        // Regex breakdown:
        // ^     = Start of the string.
        // \d+   = Match one or more digits (0-9).
        //
        // Examples:
        // "2H2O"  -> Matches "2"
        // "12NaCl" -> Matches "12"
        // "H2O"   -> No match because the string doesn't start with a digit.
        if (preg_match('/^\d+/', $reactant, $matches)) {
            $this->coefficient = (int) $matches[0];
        }

        $substancesStr = Str::after($reactant, $this->coefficient);
        if (!$substancesStr) {
            throw new InvalidArgumentException('Reactant must contain at least one substance');
        }

        // Remove every valid substance pattern from the string.
        // If anything remains afterward, the reactant contains invalid characters
        // or symbols that aren't recognized by SUBSTANCE_REGEX.
        $isMalformed = preg_replace(self::SUBSTANCE_REGEX, '', $substancesStr);
        if ($isMalformed) {
            throw new InvalidArgumentException('Reactant must contain only substance symbols');
        }

        // A single element with a subscript making up the whole reactant ex: (H2, O2, N2)
        //
        // Regex breakdown:
        // ^            = Start of the string.
        // [A-Z]        = Exactly one uppercase letter (element symbol begins uppercase).
        // [a-z]?       = Optionally match one lowercase letter (e.g. Fe, Cl, Na).
        // <sub>        = Literal opening subscript HTML tag.
        // [0-9]+       = One or more digits (the subscript value).
        // <\/sub>      = Literal closing subscript HTML tag. The slash is escaped.
        // $            = End of the string.
        //
        // This ensures the ENTIRE string consists of one element symbol
        // followed by a numeric subscript.
        $isomerPattern = '/^[A-Z][a-z]?<sub>[0-9]+<\/sub>$/';
        $isIsomer = (bool) preg_match($isomerPattern, $substancesStr);

        // Build a regex alternation containing every known polyatomic ion.
        //
        // For each ion:
        // 1. Find digits that represent subscripts.
        // 2. Wrap those digits in <sub> tags.
        // 3. Exclude single-element isomer matches when $isIsomer is true.
        // 4. Join all remaining ion patterns with | (OR).
        //
        // Example:
        // SO4 becomes SO<sub>4</sub>
        // NO3 becomes NO<sub>3</sub>
        //
        // (?<=[A-Za-z)]) = Positive lookbehind:
        // Only match digits that immediately follow a letter or closing parenthesis.
        // This prevents unrelated digits from being treated as subscripts.
        //
        // (\d+) = Capture one or more digits so they can be wrapped in <sub> tags.
        $polyatomicPattern = PolyatomicIon::query()->get()
            ->map(fn (PolyatomicIon $ion) => Str::replaceMatches('/(?<=[A-Za-z)])(\d+)/', '<sub>$1</sub>', $ion->symbol))
            ->reject(fn (string $ion) => $isIsomer && preg_match($isomerPattern, stripslashes($ion)))
            ->join('|');

        // Unique element symbols (Fe, Cu, etc. appear once per charge), longest first so alternation prefers "Hg" over "H"
        $monatomicCations = MonatomicCation::query()->with('element')->get()
            ->map(fn (MonatomicCation $cation) => $cation->element->symbol)
            ->unique()
            ->sortByDesc(fn (string $symbol) => strlen($symbol))
            ->join('|');

        // Build a regex alternation containing all known polyatomic cations.
        //
        // Subscript conversion works the same way as above:
        // (?<=[A-Za-z)]) = Only match digits following a letter or closing parenthesis.
        // (\d+)          = Capture one or more digits.
        //
        // Example:
        // NH4 becomes NH<sub>4</sub>
        $polyatomicCations = PolyatomicIon::query()->where('is_cation', true)->get()
            ->map(fn (PolyatomicIon $ion) => Str::replaceMatches('/(?<=[A-Za-z)])(\d+)/', '<sub>$1</sub>', $ion->symbol))
            ->join('|');

        // A monatomic cation (with optional subscript/charge) leading the reactant,
        // immediately followed by a polyatomic ion ex: (Na2SO4, KNO3)
        //
        // Regex breakdown:
        //
        // ^ = Start of the string.
        //
        // (?:$monatomicCations)
        //     ?: = Non-capturing group. Groups the alternatives without storing a capture.
        //     $monatomicCations = Matches one of the known monatomic cation symbols.
        //
        // (?![a-z])
        //     Negative lookahead.
        //     Ensures the next character is NOT a lowercase letter.
        //     Prevents matching "H" when the actual element is "Hg".
        //
        // (?:<sub>[0-9]+<\/sub>)?
        //     Optional non-capturing group.
        //     Matches an optional subscript containing one or more digits.
        //     ? means the entire group is optional.
        //
        // (?:<sup>[0-9]*[+\-]<\/sup>)?
        //     Optional non-capturing group for an ionic charge.
        //     <sup> = Literal opening superscript tag.
        //     [0-9]* = Zero or more digits (allows charges such as + or 2+).
        //     [+\-] = Either a literal plus or minus sign.
        //     <\/sup> = Literal closing superscript tag.
        //
        // (?:$polyatomicPattern)
        //     Matches one of the known polyatomic ions.
        //
        // Example matches:
        // Na<sub>2</sub>SO<sub>4</sub>
        // KNO<sub>3</sub>
        $cationBeforePolyatomicPattern = "/^(?:$monatomicCations)(?![a-z])(?:<sub>[0-9]+<\/sub>)?(?:<sup>[0-9]*[+\-]<\/sup>)?(?:$polyatomicPattern)/";

        $hasCationBeforePolyatomic = $monatomicCations !== ''
            && $polyatomicPattern !== ''
            && (bool) preg_match($cationBeforePolyatomicPattern, $substancesStr);

        if ($hasCationBeforePolyatomic)
        {
            $this->substances->push(new Substance($substancesStr, true));
            return;
        }

        // TODO: Psudeocode for another early return for another polyatomic case.
        // Check if there is a polyatomic ion at all.
        // Check polyatomic cation + polyatomic ion.
        // Check if the ENTIRE THING matches any polyatomic ion.
        // Update regex to include sup and need to include the charge when matching with polyatomic elements.

        if (preg_match("/^(?:$polyatomicPattern)$/", $substancesStr) && preg_match("/^(?:$polyatomicCations)$/", $substancesStr) ) {
            $this->substances->push(new Substance($substancesStr, true));
            return;
        }

        // TODO: drews idea:
        // Right now i think the issue is that if polyatomic is inside of our reactant then it has issues.
        // Past this point the entire thing needs to match the polyatomic ion.

        // We know for sure at this point we aren't dealing with any silly polyatomic stuffs, so normal!

        // Find ALL occurrences of valid substance patterns in the reactant.
        //
        // SUBSTANCE_REGEX identifies:
        // - An element symbol (e.g. H, Fe, Cl).
        // - An optional subscript (e.g. <sub>2</sub>).
        // - An optional ionic charge (e.g. <sup>2+</sup>).
        //
        // preg_match_all stores every match in $substanceMatches[0].
        preg_match_all(self::SUBSTANCE_REGEX, $substancesStr, $substanceMatches);

        // Process each matched substance individually.
        foreach ($substanceMatches[0] as $item) {
            $sub = new Substance($item);
            $sub->isIsomer = $isIsomer;
            $this->substances->push($sub);
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
            $this->nonMetalSpecific($nonPolySubstances, $elements);
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

            return $this->substances->reduce(fn ($carry, $sub) => $carry + $sub->charge);
        }

        return $this->substances->first()->isPolyatomic
            ? $this->substances->first()->charge
            : $this->substances->first()->valencies->first()->valency;
    }

    public function setAtomCount(int $atomCount): void
    {
        if ($this->substances->count() == 1) {
            $this->substances->first()->atom = $atomCount;

            return;
        }

        $this->substances->each(function (Substance $substance) use ($atomCount) {
            $substance->atom *= $atomCount;
        });
    }

    public function getValencyOrOxidationState(string $symbol, bool $useValency): int
    {
        if ($useValency) {
            return $this->valencyLookup($symbol)->first()->valency;
        }

        return $this->oxidationLookup($symbol);
    }

    /**
     * @param Collection<int, Substance> $substancesCollection
     * @param Collection<int, Element> $elements
     * @param bool $includesMetals
     * @return void
     */
    public function calculateCharges(Collection $substancesCollection, Collection $elements, bool $includesMetals): void
    {
        $elements = $elements->keyBy('symbol');

        $substancesCollection->each(function (Substance $current, int $idx) use ($substancesCollection, $elements, $includesMetals) {

            $next = $substancesCollection[$idx + 1] ?? null;

            if (!$current->charge) {
                $currentElementValency = $this->getValencyOrOxidationState($current->element, $includesMetals);
                $current->charge = $currentElementValency;
            }

            if ($next) {

                $nextElementValency = $this->getValencyOrOxidationState($next->element, $includesMetals);
                $next->charge = $nextElementValency;

                $currentElementElectronegativity = $elements[$current->element]->electronegativity;
                $nextElementElectronegativity = $elements[$next->element]->electronegativity;

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

    /**
     * @param Collection $nonPolySubstances
     * @param Collection $elements
     * @return void
     */
    public function nonMetalSpecific(Collection $nonPolySubstances, Collection $elements): void
    {
        $hardSetCharges = collect([
            'F' => -1,
            'H' => 1,
            'O' => -2,
        ]);

        $nonPolySubstances->whereIn('element', $hardSetCharges->keys())
            ->each(function (Substance $substance) use ($hardSetCharges) {
                $substance->charge = $hardSetCharges[$substance->element];
            });

        $nonPolySubstances
            ->filter(fn(Substance $substance) => $elements->where('type.name', 'halogen')->some('symbol', $substance->element))
            ->each(function (Substance $halogenSubstance) {
                $halogenSubstance->charge = -1;
            });

        $specialCases = $nonPolySubstances->whereIn('element', self::NET_NEUTRAL_CHARGE);

        $this->netCharge = 0;
        $nonPolySubstances->filter(fn($el) => !in_array($el->element, self::NET_NEUTRAL_CHARGE))
            ->each(function (Substance $sub) {
                $this->netCharge += $sub->charge * $sub->atom;
            });

        $specialCases->each(function (Substance $sub) {
            $sub->charge = $this->netCharge * -1;
            $this->netCharge = 0;
        });
    }

    // TODO: Figure out where to call him and order the formal charges
    public function lewisStructure(): Collection
    {
        $service = new LewisService();

        // Step 2
        // Step 3
        $service->calculateTotalValenceElectrons($this->substances);

        // step 4
        $service->assignCentralAtom($this->substances, $this->calculatedStructures->keys());

        if (empty($service->centralAtom)) {
            return $this->calculatedStructures;
        }

        // step 4.1 Assign connectitivities
        if (! $service->setupConnectivity($this->substances))
        {
            $this->calculatedStructures[$service->centralAtom->element] = $service;
            return $this->lewisStructure();
        }

        // Step 5 & 6
        $service->assignDefaultBonds();

        // Step 7
        $service->assignOutsideBondsLonePairs();

        // Step 10
        $service->calculateFormalCharges();


        $copier = new DeepCopy();
        $previousLewisHamilton = $copier->copy($service);

        // Step 11 loop
        while($service->hasUnfavorableCharges())
        {
            // Step 11
            $service->upgradeBonds();

            // Step 7
            $service->assignOutsideBondsLonePairs();

            // Step 10
            $service->calculateFormalCharges();

            // Confirm continue
            $currentFormalCharge = $service->formalChargeScore;

            if ($previousLewisHamilton->formalChargeScore <= $currentFormalCharge)
            {
                $service = $previousLewisHamilton;
                break;
            }
            $previousLewisHamilton = $copier->copy($service);

        }

        $this->calculatedStructures[$service->centralAtom->element] = $service;

        return $this->lewisStructure();
    }
}
