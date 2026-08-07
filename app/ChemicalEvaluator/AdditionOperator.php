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

        $elements = Element::query()
            ->whereIn('symbol', [
                ...$left->substances->pluck('element'),
                ...$right->substances->pluck('element')
            ])
            ->get()
            ->keyBy('symbol');

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
            $leftChargeValency += $left->substances->first()->isPolyatomic
                ? $left->substances->first()->charge
                : $left->substances->first()->valencies->first()->valency;
        }

        $rightIsCompound = $right->substances->count() > 1;
        $rightChargeValency = 0;
        if ($rightIsCompound)
        {
            foreach ($right->substances as $sub)
            {
                $rightChargeValency += $sub->charge;
            }
        }
        else {
            $rightChargeValency += $right->substances->first()->isPolyatomic
                ? $right->substances->first()->charge
                : $right->substances->first()->valencies->first()->valency;
        }

        $leftAtomCount = $this->calculateAtom($leftChargeValency, $rightChargeValency);
        $rightAtomCount = $this->calculateAtom($rightChargeValency, $leftChargeValency);

        if ($left->substances->count() != 1)
        {
            $left->substances->each(function (Substance $substance) use ($leftAtomCount) {
                $substance->atom *= $leftAtomCount;
            });
        } else {
            $left->substances->first()->atom = $leftAtomCount;
        }

        if ($right->substances->count() != 1)
        {
            $right->substances->each(function (Substance $substance) use ($rightAtomCount) {
                $substance->atom *= $rightAtomCount;
            });
        } else {
            $right->substances->first()->atom = $rightAtomCount;
        }

        $substances = $left->substances;

        foreach($right->substances as $sub)
        {
            $substance = $substances->first(function (Substance $substance) use ($sub){
                return $substance->element == $sub->element;
            });

            if ($substance)
            {
                $substance->atom += $sub->atom;
                continue;
            }

            $substances[] = $sub;
        }

        $sortedSubstances = $substances->sortBy(function (Substance $sub) use ($elements) {
            return $elements[$sub->element]->electronegativity ?? 0;
        });

        $stringified = $sortedSubstances->map(fn (Substance $sub) => $sub->__toString())->join('');
        $result = new Reactant($stringified);

        $result->coefficient = $left->coefficient;

        return $result;
    }
}
