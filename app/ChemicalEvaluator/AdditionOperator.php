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

        $elements = Element::query()
            ->whereIn('symbol', [
                ...$left->substances->pluck('element'),
                ...$right->substances->pluck('element'),
            ])
            ->get()
            ->keyBy('symbol');

        $leftChargeValency = $this->getChargeValency($left);
        $rightChargeValency = $this->getChargeValency($right);

        $leftAtomCount = $this->calculateAtom($leftChargeValency, $rightChargeValency);
        $rightAtomCount = $this->calculateAtom($rightChargeValency, $leftChargeValency);

        $this->setAtomCount($left, $leftAtomCount);
        $this->setAtomCount($right, $rightAtomCount);

        $substances = $this->consolidateSubstances($left, $right);

        $sortedSubstances = $substances->sortBy(function (Substance $sub) use ($elements) {
            return $elements[$sub->element]->electronegativity ?? 0;
        });

        $stringified = $sortedSubstances->map(fn (Substance $sub) => $sub->__toString())->join('');
        $result = new Reactant($stringified);

        $result->coefficient = $left->coefficient;

        return $result;
    }

    public function getChargeValency(Reactant $reactant): int
    {
        $isCompound = $reactant->substances->count() > 1;
        if (! $isCompound) {
            return $reactant->substances->first()->isPolyatomic
                ? $reactant->substances->first()->charge
                : $reactant->substances->first()->valencies->first()->valency;
        }

        return $reactant->substances->reduce(fn ($sub) => $sub?->charge ?? 0);
    }

    public function setAtomCount(Reactant $reactant, int $atomCount): void
    {
        if ($reactant->substances->count() == 1) {
            $reactant->substances->first()->atom = $atomCount;

            return;
        }

        $reactant->substances->each(function (Substance $substance) use ($atomCount) {
            $substance->atom *= $atomCount;
        });
    }

    public function consolidateSubstances(Reactant $left, Reactant $right): Collection
    {
        $substances = $left->substances;

        $right->substances->each(function (Substance $sub) use ($substances) {
            $substance = $substances->first(function (Substance $substance) use ($sub) {
                return $substance->element == $sub->element;
            });

            if ($substance) {
                $substance->atom += $sub->atom;

                return;
            }

            $substances[] = $sub;
        });

        return $substances;
    }
}
