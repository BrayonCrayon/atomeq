<?php

namespace App\ChemicalEvaluator;

use App\Models\Element;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class LewisService
{
    use ChemicalHelpers;

    public string $centralAtom = '';
    public int $totalValenceElectrons = 0;
    public int $remainingValenceElectrons = 0;
    public Collection $bonds;

    public function __construct()
    {
        $this->bonds = collect();
    }

    public function assignCentralAtom(Collection $substances): void
    {
        $this->centralAtom = $substances
            ->filter(fn($sub) => $sub->element !== "H")
            ->sortBy(fn($sub) => $this->electronegativeLookup($sub->element))
            ->first()->element;
    }

    public function calculateTotalValenceElectrons(Collection $substances): void
    {
        $substances->each(function (Substance $sub) {
            $valence = Element::query()->where('symbol', $sub->element)->first()->valence;
            $this->totalValenceElectrons += ($valence * $sub->atom) - $sub->ionCharge;
        });

        $this->remainingValenceElectrons = $this->totalValenceElectrons;
    }

    public function assignDefaultBonds(Collection $substances): void
    {
        $substances->each(function ($sub) {
            if ($sub->element == $this->centralAtom) {
                return;
            }

            for ($i = 0; $i < $sub->atom; ++$i) {
                $this->bonds->push(new Bond($sub->element, $this->centralAtom, 1));
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
}
