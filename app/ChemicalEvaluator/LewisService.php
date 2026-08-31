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

    const array INVAILD_CENTRAL_ATOMS = ['H', 'F', 'Cl', 'Br', 'I'];
    const array TERMINAL_ATOMS = ['H', 'F', 'Cl', 'Br', 'I'];

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

    public function setupConnectivity(Collection $substances): void
    {
        $connectivity = [];
        /** @var Collection<int, string> $elementBucket */
        $elementBucket = $substances->map(fn(Substance $sub) => $sub->element)
            ->filter(fn(string $element) => $element !== $this->centralAtom);

        $terminalBucket = $elementBucket->filter(fn(string $atom) => collect(self::INVAILD_CENTRAL_ATOMS)->contains($atom));
        $remainingBucket = $elementBucket->filter(fn(string $atom) => !$terminalBucket->contains($atom));
        // use FUNCTIONAL_CONNECTIVITY to determine how things should connect up with whom.


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

    public function assignDefaultBonds(Collection $substances): void
    {
        $substances->each(function (Substance $substance) {
            if ($substance->element == $this->centralAtom) {
                return;
            }

            if ($substance->isPolyatomic) {
                $this->assignDefaultBonds($substance->polyatomicSubstances);
            }

            for ($i = 0; $i < $substance->atom; ++$i) {
                $this->bonds->push(new Bond($substance->element, $this->centralAtom, 1));
                $this->remainingValenceElectrons -= 2;
            }
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
                $bondedElectrons = $this->bonds->where('centralElement', $symbol)->sum(fn (Bond $bond) => $bond->order * 2);
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
                ->each(fn (Bond $bond) => $bond->order++);
        });
    }

    public function hasUnfavorableCharges(): bool
    {
        return $this->formalCharges->flatten()->some(fn(int $value) => $value > 0 || $value < 0);
    }
}
