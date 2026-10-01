<?php

namespace App\ChemicalEvaluator;

use App\Models\Element;
use Illuminate\Support\Collection;

class LewisService
{
    use ChemicalHelpers;

    public string $centralAtom = '';
    public int $totalValenceElectrons = 0;
    public int $remainingValenceElectrons = 0;
    public Collection $bonds;
    public Collection $formalCharges;

    public Collection $previousFormalCharges;

    /** @var Collection<int, array{0: string, 1: string}> [outer, host] pairs */
    public Collection $connectivity;

    const array INVAILD_CENTRAL_ATOMS = ['H', 'F', 'Cl', 'Br', 'I'];
    const array TERMINAL_ATOMS = ['H', 'F', 'Cl', 'Br', 'I'];
    const array EXPANDED_OCTET_ATOMS = ['Si', 'P', 'S', 'Cl', 'As', 'Se', 'Br', 'I', 'Xe'];
    const array CONNECTIVITY_SCORES = ['preferred' => 2, 'possible' => 1];

    public int $centralElementAtoms {
        get => $this->totalValenceElectrons - $this->bonds->sum('storedElectrons');
    }

    public bool $hasUpdatingChanged {
        get => $this->formalCharges->toArray() !== $this->previousFormalCharges->toArray();
    }

    public function __construct()
    {
        $this->bonds = collect();
        $this->formalCharges = collect();
        $this->previousFormalCharges = collect();
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

        $this->centralAtom = $substance->element;
    }

    /**
     * TODO: Returns false when an atom has nowhere to go, meaning this central atom can't work.
     */
    public function setupConnectivity(Collection $substances): bool
    {
        $this->connectivity = collect();

        $atoms = $this->expandAtoms($substances)->map(fn($element) => new BondedElement($element));
        $valences = Element::query()
            ->whereIn('symbol', $atoms->pluck('element')->unique())
            ->pluck('valence', 'symbol');

        $centralIndex = $atoms->search(fn($item) => $item->element === $this->centralAtom);
        $placed = [
            $centralIndex => [
                'depth' => 0,
                'openSlots' => $this->bondCapacity($this->centralAtom, $valences[$this->centralAtom], true),
            ],
        ];

        $unplaced = collect($atoms)
            ->except($centralIndex)
            ->sortBy(fn(string $element) => [
                in_array($element, self::TERMINAL_ATOMS) ? 1 : 0,
                $this->electronegativeLookup($element),
            ]);

        foreach ($unplaced as $index => $element) {
            $hostIndex = collect($placed)
                ->filter(fn(array $host) => $host['openSlots'] > 0)
                ->map(fn(array $host, int $i) => [...$host, 'score' => $this->connectivityScore($element, $atoms[$i])])
                ->filter(fn(array $host) => $host['score'] > 0)
                ->sortBy([['score', 'desc'], ['depth', 'asc'], ['openSlots', 'desc']])
                ->keys()
                ->first();

            if ($hostIndex === null) {
                $this->connectivity = collect();
                return false;
            }

            $this->connectivity->push([$element, $atoms[$hostIndex]]);
            $placed[$hostIndex]['openSlots']--;
            $placed[$index] = [
                'depth' => $placed[$hostIndex]['depth'] + 1,
                'openSlots' => $this->bondCapacity($element, $valences[$element]) - 1,
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
            ->values();
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

        $this->remainingValenceElectrons = $this->totalValenceElectrons;
    }

    public function assignDefaultBonds(): void
    {
        $this->connectivity->each(function ($pair) {
            $this->bonds->push(new Bond($pair[0], $pair[1], 1));
            $this->remainingValenceElectrons -= 2;
        });
    }

    public function assignOutsideBondsLonePairs(): void
    {
        $this->bonds->each(function (Bond $bond) {
            $toMoveOver = (8 - $bond->storedElectrons);

            if ($bond->bondedElement == 'H') {
                return;
            }

            $this->remainingValenceElectrons -= $toMoveOver;
            $bond->storedElectrons = 8;
        });
    }

    public function calculateFormalCharges(): void
    {
        $listOfSymbols = collect([
            ...$this->bonds->pluck('bondedElement')->unique(),
            $this->centralAtom
        ]);

        $listOfSymbols->each(fn($symbol) => $this->formalCharges[$symbol] = collect());
        $elements = Element::query()->whereIn('symbol', $listOfSymbols)->get();

        $this->formalCharges->each(function (Collection $item, string $symbol) use ($elements) {
            $valenceElectrons = $elements->where('symbol', $symbol)->first()->valence;

            if ($this->centralAtom !== $symbol) {

                $this->bonds->where('bondedElement', $symbol)->each(function ($bond) use($valenceElectrons, $item){

                    $bondElectrons = $bond->order * 2;
                    $formalCharge = $valenceElectrons - (($bond->storedElectrons - $bondElectrons) + ($bondElectrons / 2));
                    $item->push($formalCharge);
                });
            } else {
                $bondedElectrons = $this->bonds->where('centralElement', $symbol)->sum(fn (Bond $bond) => $bond->level * 2);
                $formalCharge = $valenceElectrons - (($this->remainingValenceElectrons) + ($bondedElectrons / 2));
                $item->push($formalCharge);
            }
        });
    }

    public function upgradeBonds(): void
    {
        $atomsToUpgrade = $this->formalCharges
            ->filter(fn(Collection $charges, string $symbol) => $charges->sum() !== 0 && $symbol !== $this->centralAtom)
            ->map(fn($_, string $symbol) => $symbol);

        $this->previousFormalCharges = new Collection($this->formalCharges);

        $atomsToUpgrade->each(function(string $symbol) {
            $this->bonds->filter(fn (Bond $bond) => $bond->bondedElement === $symbol)
                ->each(fn (Bond $bond) => $bond->level++);
        });
    }

    public function hasUnfavorableCharges(): bool
    {
        return $this->formalCharges->flatten()->some(fn(int $value) => $value > 0 || $value < 0);
    }
}
