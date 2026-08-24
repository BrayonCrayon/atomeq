<?php

namespace App\ChemicalEvaluator;

use App\Models\Element;
use App\Models\PolyatomicIon;
use App\Models\Valency;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Substance
{
    use ChemicalHelpers;
    const SUBSTANCE_REGEX = '/[A-Z][a-z]?(?:<sub>[0-9]+<\/sub>)?/';

    // $atom: The number of atoms of a substance. It is the number following the substance ex: (H2, Na3, Cl7)
    // $element: The name of the substance ex: (H, Na, Cl)
    // $charge: Indicates whether the substance is positively(cation)/negatively(anion) charged or unknown for null.
    public ?int $atom = null;
    public string $element = '';
    public ?float $charge = null;
    public int $ionCharge = 0;

    /** @var Collection<int, Substance>|null  */
    public Collection|null $polyatomicSubstances = null;

    /** @var Collection<int, Valency>|null  */
    public Collection|null $valencies = null;

    public function __construct(string $substance, public bool $isPolyatomic = false)
    {
        $this->polyatomicSubstances = collect();
        $this->isPolyatomic ? $this->parsePolyatomicIon($substance) : $this->parseSubstance($substance);
    }

    public function getSafeValencies(): Collection
    {
        return $this->valencies ?? $this->polyatomicSubstances?->first()?->valencies;
    }

    public function parseSubstance(string $substance): void
    {
        $this->atom = 1;
        if (preg_match('/[a-zA-Z]+/', $substance, $matches)) {
            $this->element = $matches[0];
        } else {
            throw new InvalidArgumentException('Substance must be a valid element.');
        }

        if (preg_match('/<sub>(\d+)<\/sub>/', $substance, $matches)) {
            $this->atom = (int) $matches[1];
        }

        if(preg_match('/<sup>(\d*[+\-])<\/sup>/', $substance, $matches)) {
            $ionValue = Str::length($matches[1]) > 1 ? Str::numbers($matches[1]) : 1;
            $sign = Str::endsWith($matches[1], '-')? '-': '+';
            $this->ionCharge = (int) ($sign . $ionValue);
        }

        $this->valencies = $this->valencyLookup($this->element);
    }

    public function parsePolyatomicIon(string $substance): void
    {
        $leftOvers = preg_replace(self::SUBSTANCE_REGEX, '', $substance);

        if ($leftOvers) {
            throw new InvalidArgumentException('Reactant must contain only substance symbols');
        }

        if (preg_match('/[a-zA-Z]+/', $substance, $matches)) {
            $this->element = $matches[0];
        } else {
            throw new InvalidArgumentException('Substance must be a valid element.');
        }

        preg_match_all(self::SUBSTANCE_REGEX, $substance, $matches);
        collect(...$matches)->each(function ($match) {
            $this->polyatomicSubstances->add(new Substance($match));
        });

        $symbol = preg_replace('/<\/?sub>/', '', $substance);
        $this->charge = PolyatomicIon::query()->whereSymbol($symbol)->first()->charge ?? null;
    }

    public function __toString(): string
    {
        $string = $this->element;
        if ($this->atom > 1) {
            $string .= "<sub>{$this->atom}</sub>";
        }
        return $string;
    }
}
