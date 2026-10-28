<?php

namespace App\ChemicalEvaluator;

use App\Models\Element;
use Illuminate\Support\Collection;

class LewisService
{
    use ChemicalHelpers;

    public int $totalValenceElectrons = 0;
    public BondedElement $centralAtom;
    public Collection $bonds;
    public Collection $bondedElements;
    public int $formalChargeScore {
        get => $this->bondedElements->sum(fn (BondedElement $ele) => abs($ele->formalCharge));
        set(int $value) {}
    }

    /** @var Collection<int, array{0: string, 1: string}> [outer, host] pairs */
    public Collection $connectivity;

    const array INVAILD_CENTRAL_ATOMS = ['H', 'F', 'Cl', 'Br', 'I'];
    const array TERMINAL_ATOMS = ['H', 'F', 'Cl', 'Br', 'I'];
    const array EXPANDED_OCTET_ATOMS = ['Si', 'P', 'S', 'Cl', 'As', 'Se', 'Br', 'I', 'Xe'];
    const array CONNECTIVITY_SCORES = ['preferred' => 2, 'possible' => 1];

    public function __construct()
    {
        $this->bonds = collect();
        $this->bondedElements = collect();
        $this->connectivity = collect();
    }

    public function assignCentralAtom(Collection $substances, Collection $alreadyTried): void
    {
        $substance = $substances
            ->map(fn(Substance $sub) => $sub->isPolyatomic ? $sub->polyatomicSubstances : $sub)
            ->flatten()
            ->filter(fn($sub) => !collect(self::INVAILD_CENTRAL_ATOMS)->contains($sub->element))
            ->filter(fn($sub) => ! $alreadyTried->contains($sub->element) )
            ->sortBy(fn($sub) => $this->electronegativeLookup($sub->element))
            ->first();

        if (!$substance) {
            return;
        }

        $this->centralAtom = new BondedElement($substance->element);
    }

    /**
     * TODO: Returns false when an atom has nowhere to go, meaning this central atom can't work.
     */
    public function setupConnectivity(Collection $substances): bool
    {
        $this->connectivity = collect();

        $this->bondedElements = $this->expandAtoms($substances);

        $valences = Element::query()
            ->whereIn('symbol', $this->bondedElements->pluck('element')->unique())
            ->pluck('valence', 'symbol');

        $centralIndex = $this->bondedElements->search(fn($item) => $item->element === $this->centralAtom->element);
        $this->bondedElements[$centralIndex] = $this->centralAtom;

        $placed = [
            $centralIndex => [
                'depth' => 0,
                'openSlots' => $this->bondCapacity($this->centralAtom->element, $valences[$this->centralAtom->element], true),
            ],
        ];

        $unplaced = $this->bondedElements
            ->except($centralIndex)
            ->sortBy(fn(BondedElement $item) => [
                in_array($item->element, self::TERMINAL_ATOMS) ? 1 : 0,
                $this->electronegativeLookup($item->element),
            ]);

        foreach ($unplaced as $index => $bondedElement) {
            $hostIndex = collect($placed)
                ->filter(fn(array $host) => $host['openSlots'] > 0)
                ->map(function (array $host, int $i) use ($bondedElement) {
                    return [
                        ...$host,
                        'score' => $this->connectivityScore($bondedElement->element, $this->bondedElements[$i]->element)
                    ];
                })
                ->filter(fn(array $host) => $host['score'] > 0)
                ->sortBy([['score', 'desc'], ['depth', 'asc'], ['openSlots', 'desc']])
                ->keys()
                ->first();

            if ($hostIndex === null) {
                $this->connectivity = collect();
                return false;
            }

            $this->connectivity->push([$bondedElement, $this->bondedElements[$hostIndex]]);
            $placed[$hostIndex]['openSlots']--;
            $placed[$index] = [
                'depth' => $placed[$hostIndex]['depth'] + 1,
                'openSlots' => $this->bondCapacity($bondedElement->element, $valences[$bondedElement->element]) - 1,
            ];
        }

        return true;
    }

    /**
     * How many single bonds an atom can form in the skeleton:
     *  - terminal atoms (H, halogens) → 1
     *  - a period 3+ central atom can expand its octet → one bond per valence electron (P → 5, S → 6)
     *  - everyone else → the electrons it needs to reach an octet (O → 2, N → 3, C → 4)
     */
    private function bondCapacity(string $element, int $valence, bool $isCentral = false): int
    {
        if (in_array($element, self::TERMINAL_ATOMS)) {
            return 1;
        }

        if ($isCentral && in_array($element, self::EXPANDED_OCTET_ATOMS)) {
            return $valence;
        }

        return 8 - $valence;
    }

    private function connectivityScore(string $a, string $b): int
    {
        return max(
            self::CONNECTIVITY_SCORES[self::FUNCTIONAL_CONNECTIVITY[$a][$b] ?? ''] ?? 0,
            self::CONNECTIVITY_SCORES[self::FUNCTIONAL_CONNECTIVITY[$b][$a] ?? ''] ?? 0,
        );
    }

    /**
     * One entry per atom, e.g. H₂SO₄ → ['H', 'H', 'S', 'O', 'O', 'O', 'O']
     */
    private function expandAtoms(Collection $substances): Collection
    {
        return $substances
            ->flatMap(fn(Substance $sub) => $sub->isPolyatomic
                ? $this->expandAtoms($sub->polyatomicSubstances)
                : array_fill(0, $sub->atom, $sub->element))
            ->values()
            ->map(fn ($element) => $element instanceof BondedElement ? $element : new BondedElement($element));
    }

    public function calculateTotalValenceElectrons(Collection $substances): void
    {
        $substances->each(function (Substance $sub) {
            if ($sub->isPolyatomic) {
                $this->calculateTotalValenceElectrons($sub->polyatomicSubstances);
                return;
            }

            $valence = Element::query()->where('symbol', $sub->element)->first()->valence;
            $this->totalValenceElectrons += ($valence * $sub->atom) - $sub->ionCharge;
        });
    }

    public function assignDefaultBonds(): void
    {
        $this->connectivity->each(function ($pair) {
            $this->bonds->push(new Bond($pair[0], $pair[1], 1));
        });
    }

    public function assignOutsideBondsLonePairs(): void
    {
        $bondedUsedPairs = $this->bonds->sum('level');
        $this->centralAtom->lonePairs = ($this->totalValenceElectrons / 2) - $bondedUsedPairs;

        $this->bondedElements->each(function (BondedElement $bondedElement) {

            if ($bondedElement->element === 'H') {
                return;
            }

            if ($this->centralAtom !== $bondedElement) {
                $remainingOctetPairs = (4 - $bondedElement->bondedPairs);

                if ($this->centralAtom->lonePairs === 0 || $this->centralAtom->lonePairs - $remainingOctetPairs < 0) {
                    return;
                }

                $this->centralAtom->lonePairs -= $remainingOctetPairs;
                $bondedElement->lonePairs = $remainingOctetPairs;
            }
        });
    }

    public function calculateFormalCharges(): void
    {
        $listOfSymbols = $this->bondedElements->pluck('element')->unique();

        $elements = Element::query()->whereIn('symbol', $listOfSymbols)->get();

        $this->bondedElements->each(function (BondedElement $bondedElement) use ($elements) {
            $valenceElectrons = $elements->where('symbol', $bondedElement->element)->first()->valence;
            $bondedElement->formalCharge = $valenceElectrons - $bondedElement->loneElectrons - $bondedElement->bondedPairs;
        });
    }

    public function upgradeBonds(): void
    {
        $atomsToUpgrade = $this->bondedElements
            ->filter(fn(BondedElement $item) => $item->formalCharge !== 0 && $item !== $this->centralAtom);

        $atomsToUpgrade->each(function(BondedElement $item) {
            /** @var Bond $bondToUpgrade */
            $bondToUpgrade = $item->connections->first(fn(Bond $bond) => $bond->rightElement->element !== 'H');

            if ($this->canUpgrade($bondToUpgrade))
            {
                $bondToUpgrade->level++;
            }
        });
    }

    public function hasUnfavorableCharges(): bool
    {
        return $this->bondedElements->some(function (BondedElement $element) {
            return $element->formalCharge > 0 || $element->formalCharge < 0;
        });
    }

    private function canUpgrade(Bond $bond): bool
    {
        $maxElectrons = collect(self::EXPANDED_OCTET_ATOMS)->contains($bond->rightElement->element) ? 12 : 8;
        return $bond->leftElement->lonePairs >= 1 && $bond->rightElement->totalElectrons <= $maxElectrons -2;
    }
}
