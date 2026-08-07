# AdditionOperator Bug Analysis

Findings from investigating the three failing `EvaluatorTest` cases. All bugs trace back to
`AdditionOperator.php` and gaps in how net charge is calculated for multi-substance compounds.

---

## Failing Tests

### Test 7 — `3Na + 3H + 3C + 2O₂ =` → `3NaHCO` (expected `3NaHCO<sub>3</sub>`)

**Root cause: `assignCharges()` assigns ±1 instead of signed valency magnitudes.**

The RPN evaluates left-to-right: `((Na+H)+C)+O₂`. By the final step `[NaHC] + [2O₂]`,
`AdditionOperator` computes `leftChargeValency` by summing `substance.charge` values.
Those values come from `assignCharges()`, which only ever sets ±1 regardless of actual valency.

For NaHC, `assignCharges` runs pairwise EN comparisons:

| Pair | EN comparison | Charge assigned |
|------|--------------|-----------------|
| Na vs H | Na(0.93) < H(2.20) | Na=+1, H=−1 |
| H vs C | H(2.20) < C(2.55) | H=+1 (overwritten), C=−1 |

Sum with ±1 charges: +1 + 1 + (−1) = **+1** (wrong).

Correct net charge using signed valency magnitudes: Na(+1) + H(+1) + C(+4) = **+6**.

| `leftChargeValency` | `leftAtomCount` | `rightAtomCount` | Result |
|--------------------|-----------------|-----------------|--------|
| +1 (current, wrong) | `calculateAtom(1,2)=2` | `calculateAtom(2,1)=1` | Na₂H₂C₂O → `3Na₂H₂C₂O` |
| +6 (correct) | `calculateAtom(6,2)=1` | `calculateAtom(2,6)=3` | NaHCO₃ → `3NaHCO<sub>3</sub>` ✓ |

---

### Test 8 — `2CO + O₂ =` → `2CO` (expected `2CO<sub>2</sub>`)

**Root cause: no guard for neutral compounds — `calculateAtom` produces zero/invalid counts.**

CO has net charge 0 (C.charge + O.charge = +1 − 1 = 0). Passing zero into `calculateAtom`:

```
calculateAtom(0, 2) → 2 / gcd(0,2) = 2/2 = 1   (leftAtomCount  = 1, left unchanged ✓)
calculateAtom(2, 0) → 0 / gcd(2,0) = 0/2 = 0   (rightAtomCount = 0, O₂'s atom overwritten to 0 ✗)
```

O₂'s `atom=2` is overwritten with 0. After merge, O renders with no subscript → `2CO`.

CO is genuinely neutral (carbon monoxide is a neutral molecule), so this cannot be fixed by
improving the charge magnitudes — a dedicated neutral-compound code path is required.

Per `multi-substance-addition.md`: when the left group has net charge 0, skip the crossover
and distribute the right element's atoms across the left molecules using the coefficient ratio:

```
O added per left molecule = (right.atom × right.coefficient) / left.coefficient
                          = (2 × 1) / 2 = 1
```

Each CO gains 1 O → CO₂, coefficient preserved → `2CO₂`. ✓

---

### Test 9 — `N₂ + H₂ ->` → `H<sub>3</sub>N` (expected `NH<sub>3</sub>`)

**Root cause: EN-ascending sort places H before N for all compound types.**

The crossover itself is correct: `calculateAtom(3,1)=1` for N, `calculateAtom(1,3)=3` for H,
giving N(1) and H(3). But `AdditionOperator.php:95-98` sorts all substances by
`electronegativity ASC`:

```php
$sortedSubstances = $substances->sortBy(function (Substance $sub) use ($elements) {
    return $elements[$sub->element]->electronegativity ?? 0;
});
```

H (EN=2.20) < N (EN=3.04) → H sorts first → `H<sub>3</sub>N`.

The cation-first sort is correct for ionic compounds (`Cu₂O`, `NaCl`). For molecular
synthesis reactions between non-metals (N₂ + H₂), IUPAC convention places N before H.
No rule exists in the code or docs to distinguish ionic vs molecular ordering.

---

## Missing Rules (not yet documented or implemented)

### 1. `assignCharges()` must assign signed valency magnitudes, not ±1

`multi-substance-addition.md` specifies:

> `net_charge = Σ (substance.charge × substance.atom)`

With `charge = ±1`, this formula collapses to `Σ (±atom)`, which is independent of the
element's actual ionic strength. For NaHC to have net charge +6, charges must carry the
actual valency: Na=+1, H=+1, C=+4.

**Required change:** `assignCharges()` should set `substance.charge = ±valency_magnitude`
using the DB valency (with sign from EN comparison), not a hardcoded ±1.

### 2. `netCharge(): int` method on `Reactant` — not yet implemented

Explicitly flagged in `multi-substance-addition.md`:

> **Net charge calculation** — **Not yet implemented** — needs a `netCharge(): int` method on `Reactant`

Once `assignCharges()` assigns real magnitudes, `netCharge()` returns `Σ (charge × atom)`.
`AdditionOperator` should call `$left->netCharge()` and `$right->netCharge()` instead of the
manual loop that sums raw `charge` values.

### 3. Neutral compound guard in `AdditionOperator`

`multi-substance-addition.md` describes the `2CO + O₂` case verbally but provides no code
path. The implementation needs:

```
if (left.netCharge() == 0) {
    // skip crossover
    // for each substance in right that matches a substance in left: 
    //   left.atom += right.atom * right.coefficient / left.coefficient
    // append non-matching right substances unchanged
    return result with left.coefficient
}
```

### 4. Molecular compound ordering rule — completely absent from docs

The cation-before-anion EN sort is correct for ionic compounds but produces `H<sub>3</sub>N`
for `N₂ + H₂`. No rule exists in either the docs or the code for molecular (covalent)
synthesis products. Options to consider:

- Preserve input reactant order (left substance first) instead of always sorting by EN,
  and rely on the cation-before-anion swap (Step 5) to put the cation-side as `$left`
  before the merge. This would give the correct order for both `Cu₂O` (swap applied)
  and `NH₃` (no swap, since N is already left and is the first element by convention).
- Add a specific rule for N+H: N always precedes H (IUPAC binary compound convention).

---

## Affected Files

| File | Change needed |
|------|--------------|
| `Reactant.php` | `assignCharges()` → assign `±valency_magnitude`; add `netCharge(): int` |
| `AdditionOperator.php` | Use `netCharge()`; add neutral-compound guard; revisit ordering |
| `multi-substance-addition.md` | Document `assignCharges` magnitude requirement and neutral-compound algorithm |
| `addition-operator-roadmap.md` | Add steps for `netCharge()` and neutral-compound handling |
