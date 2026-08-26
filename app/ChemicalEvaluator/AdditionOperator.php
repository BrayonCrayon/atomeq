<?php

namespace App\ChemicalEvaluator;

use App\ChemicalEvaluator\General\BinaryOperator;
use App\ChemicalEvaluator\General\Operand;
use App\Models\Element;
use Illuminate\Support\Collection;

class AdditionOperator extends BinaryOperator
{
    use ChemicalHelpers;

    public function operate(Operand $left, Operand $right): Operand
    {
        if (! $left instanceof Reactant || ! $right instanceof Reactant) {
            throw new \InvalidArgumentException('Addition operator requires Reactant operands');
        }

        $leftChargeValency = $left->getChargeValency();
        $rightChargeValency = $right->getChargeValency();

        $leftAtomCount = $this->calculateAtom($leftChargeValency, $rightChargeValency);
        $rightAtomCount = $this->calculateAtom($rightChargeValency, $leftChargeValency);

        $left->setAtomCount($leftAtomCount);
        $right->setAtomCount($rightAtomCount);

        $substances = $this->consolidateSubstances($left, $right);

        $stringified = $substances->map(fn (Substance $sub) => $sub->__toString())->join('');
        $result = new Reactant($stringified);

        $result->coefficient = $left->coefficient;

        return $result;
    }

    public function consolidateSubstances(Reactant $left, Reactant $right): Collection
    {

        $substances = $left->substances->concat($right->substances);

        $elements = Element::query()
            ->whereIn('symbol', $substances->pluck('element')->unique())
            ->orderBy('electronegativity')
            ->pluck('symbol');

        return $substances
            ->groupBy('element')
            ->sortBy(fn ($group, $element) => $elements->search($element))
            ->map(function ($group) {
                $substance = $group->first();
                $substance->atom = $group->sum('atom');

                return $substance;
            })
            ->values();
    }
}
