<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\BinaryOperator;
use App\ChemicalEvaluator\General\Operand;
use App\Models\Element;

class AdditionOperator extends BinaryOperator
{
    use ChemicalHelpers;

    public function operate(Operand $left, Operand $right): Operand
    {
        if (! $left instanceof Reactant || ! $right instanceof Reactant) {
            throw new \InvalidArgumentException('Addition operator requires Reactant operands');
        }

        $result = new Reactant();

        $elements = Element::query()
            ->whereIn('symbol', [
                ...$left->substances->pluck('element'),
                ...$right->substances->pluck('element')
            ])
            ->get()
            ->keyBy('symbol');

        // use criss-cross method from document net_charge = Σ (substance.charge × substance.atom) if multiple substances
        // Find valency/Charge value from left
        $leftIsCompound = $left->substances->count() > 1;
        $leftChargeValency = 0;
        if ($leftIsCompound)
        {
            foreach ($left->substances as $sub)
            {
                $leftChargeValency += $sub->charge;
            }
        }
        else{
            $leftChargeValency += $left->substances->first()->valencies->first()->valency;
        }

        // Find valency/charge value from the right
        $rightIsCompound = $right->substances->count() > 1;
        $rightChargeValency = 0;
        if ($rightIsCompound)
        {
            foreach ($right->substances as $sub)
            {
                $rightChargeValency += $sub->charge;
            }
        }
        else{
            $rightChargeValency += $right->substances->first()->valencies->first()->valency;
        }

        $leftAtomCount = $this->calculateAtom($leftChargeValency, $rightChargeValency);
        $rightAtomCount = $this->calculateAtom($rightChargeValency, $leftChargeValency);

        $left->substances->each(function (Substance $substance) use ($leftAtomCount) {
           $substance->atom *= $leftAtomCount;
        });

        $right->substances->each(function (Substance $substance) use ($rightAtomCount) {
            $substance->atom *= $rightAtomCount;
        });

        // Merge duplicates
        $result->substances = $left->substances;

        foreach($right->substances as $sub)
        {
            $substance = $result->substances->first(function (Substance $substance) use ($sub){
                return $substance->element == $sub->element;
            });

            if ($substance)
            {
                $substance->atom += $sub->atom;
                continue;
            }

            $result->substances[] = $sub;
        }

        $result->substances = $result->substances->sortBy(function (Substance $sub) use ($elements) {
            return $elements[$sub->element]->electronegativity ?? 0;
        });

        $result->coefficient = $left->coefficient;
        $result->assignCharges();

        return $result;
    }
}
