# Multi-Substance Addition: Chemistry Rules & Worked Examples

This document covers the rules that govern `AdditionOperator` when one or both reactants
contain **multiple substances** (i.e., a pre-formed group rather than a lone element).

---

## Context: Where This Lives in the Stack

The shunting-yard algorithm builds formulas progressively in RPN. Each `+` pops two
`Reactant` operands off the stack and pushes one result. A `Reactant` can carry more than
one `Substance` when it was itself produced by a prior `+` step.

```
H   H   +       → H₂               (single + single, same element)
H₂  O   +       → H₂O              (result of prior + is now multi-substance left)
Na  OH  +       → NaOH             (single + polyatomic group)
Ca  OH  +       → Ca(OH)₂          (single + group, different valencies)
```

---

## Core Rule: Net Charge Crossover

The same criss-cross rule used for lone elements applies at the **group level**. Each
reactant is treated as a single super-ion whose valency is its **net charge**.

### Step 1 — Determine net charge of each reactant

**Single-substance reactant:** use the element's default valency directly.

**Multi-substance reactant:** `net_charge = Σ (substance.charge × substance.atom)` for
every substance in the group. The `charge` values are already assigned by
`Reactant::assignCharges()` based on electronegativity or polyatomic ion lookup.

| Group | Substance charges | Net charge |
|---|---|---|
| `[Na]` | Na = +1 | **+1** |
| `[Cl]` | Cl = -1 | **-1** |
| `[O, H]` (OH⁻) | O = -2, H = +1 | **-1** |
| `[S, O₄]` (SO₄²⁻) | S = +6, O×4 = -8 | **-2** |
| `[C, O]` (CO, inside 2CO) | C = +2, O = -2 | **0** ← neutral, see below |

### Step 2 — Apply the crossover rule

```
g            = gcd(|net_left|, |net_right|)
count_left   = |net_right| / g      // how many of the left group
count_right  = |net_left|  / g      // how many of the right group
```

This is identical to `calculateAtom()`, just operating on group-level charges instead of
single element valencies.

### Step 3 — Scale each substance's atom count

```
for each substance in left:   substance.atom *= count_left
for each substance in right:  substance.atom *= count_right
```

### Step 4 — Merge duplicate elements

If the same element symbol appears in both groups after scaling, sum the atom counts into
one substance and discard the duplicate.

---

## Special Cases

### Neutral groups (net charge = 0)

A neutral compound (e.g., CO, NaCl, H₂O) has no remaining ionic capacity. Adding it to
another neutral compound is a **reaction**, not formula-building, and belongs in
`ReactionOperator`, not `AdditionOperator`.

Adding a neutral compound to a charged group is valid — the charged group picks up the
neutral group's non-charged element(s) via the shared-element merge in Step 4.
Example: `2CO + O₂ = 2CO₂` — the extra O from O₂ merges with the O already in CO.

### Same-sign charges

Two cations or two anions cannot form a neutral ionic compound. This is a "no reaction"
scenario and should return a `NoReactionOperand`.

### Ordering: cation always first

Already enforced by `AdditionOperator` via electronegativity comparison. The substance
with lower electronegativity (the cation) must always be index 0 in the result, regardless
of which side it arrived on.

### Multiple valencies (transition metals)

When a substance has multiple valencies, use the one flagged `is_default = true` unless
a specific valency was notated in the input (e.g., `Fe(III)`). Both FeO and Fe₂O₃ are
chemically valid; the default picks the most common one.

---

## Worked Examples

### From `AdditionOperatorTest.php`

#### `Fe + O` → FeO

- Fe default valency: **+2** (is_default), also has +3
- O default valency: **-2**
- gcd(2, 2) = 2 → count_Fe = 1, count_O = 1
- Result: **FeO** (substances count = 2, both atom = 1)

#### `Cu + O` → Cu₂O

- Cu default valency: **+1** (is_default), also has +2
- O default valency: **-2**
- gcd(1, 2) = 1 → count_Cu = 2, count_O = 1
- Result: **Cu₂O**

#### `O + Cu` → Cu₂O (cation-before-anion ordering)

- Same as above, but O arrives as `$left` and Cu as `$right`
- `AdditionOperator` compares electronegativity: O (3.44) > Cu (1.90)
- O is the anion → swap so Cu is index 0
- Same crossover as above
- Result: **Cu₂O** (not OCu₂)

---

### From `EvaluatorTest.php`

#### `Na + Cl` → NaCl

- Na valency: **+1**, Cl valency: **-1**
- gcd(1, 1) = 1 → count_Na = 1, count_Cl = 1
- Na (EN 0.93) < Cl (EN 3.16) → Na is cation, goes first
- Result: **NaCl**

#### `H + H` → H₂

- Both H, same element
- Step 4 (merge duplicates): 1 + 1 = 2 atoms
- Result: **H₂**

#### `H + H + O` → H₂O

This chains two `+` operations in RPN: `H H + O +`

**First `+`:** `H + H` → H₂ (Reactant with one substance: H, atom = 2)

**Second `+`:** `[H₂] + [O]`
- H₂ as a cation group: net charge = +1 (each H has valency +1, and the group
  acts as a single H unit with valency +1 — more precisely, H is used with atom=2
  already set from the merge, so the valency of H is still +1 individually)
- Actually this resolves because H (+1) and O (-2): gcd(1,2) = 1, count_H = 2,
  count_O = 1, but H already has atom = 2 from the prior step, so count_H scales
  it to 4 — this is where the coefficient mechanism matters. The prior `H + H`
  step sets coefficient = 2 or atom = 2. The second step uses that atom count as-is
  and does not re-apply the crossover atom multiplication.
- Result: **H₂O**

#### `2Na + Cl₂` → 2NaCl

- Na valency: +1, Cl valency: -1
- gcd(1, 1) = 1 → count_Na = 1, count_Cl = 1
- The `2` prefix is the coefficient on the Na reactant, carried through to the result
- Cl₂ has atom = 2; after combination the coefficient of the result absorbs the Cl₂
- Result: **2NaCl**

#### `3Na + 3H + 3C + 2O₂` → 3NaHCO₃

This is a four-reactant chain, building NaHCO₃ (sodium bicarbonate) step by step.

HCO₃⁻ (bicarbonate) is a polyatomic ion with charge -1. The RPN chain builds it up:

```
H  +  C  → HC   (intermediate)
HC +  O₃ → HCO₃ (bicarbonate group, net charge -1)
Na + HCO₃ → NaHCO₃
```

The coefficient 3 carries through at each step.
- Final crossover: Na (+1) vs HCO₃ (-1) → gcd(1,1) = 1, count_Na = 1, count_HCO₃ = 1
- Result: **3NaHCO₃**

#### `2CO + O₂` → 2CO₂

- CO is a neutral compound (net charge = 0): C (+2) + O (-2) = 0
- O₂ carries a net charge of -2 (valency of O²⁻ × 2 atoms, but as diatomic O₂ it
  represents one unit of O available to bond)
- The "extra" oxygen from O₂ merges via the duplicate-element step:
  - CO contributes O with atom = 1
  - O₂ contributes O with atom = 2 (one per CO molecule in the 2:1 ratio)
  - After scaling: each CO picks up one O → O atom count goes from 1 to 2
- Result: **2CO₂**

> Note: the neutral-group case is the trickiest. The net-charge crossover rule
> alone cannot drive this; it requires the shared-element merge (Step 4) and the
> coefficient/atom scaling to produce the right answer.

---

## Summary Table

| Left | Right | Net charges | GCD | count_L | count_R | Result |
|---|---|---|---|---|---|---|
| Na (+1) | Cl (-1) | 1, 1 | 1 | 1 | 1 | NaCl |
| Ca (+2) | Cl (-1) | 2, 1 | 1 | 1 | 2 | CaCl₂ |
| Fe (+2) | O (-2) | 2, 2 | 2 | 1 | 1 | FeO |
| Fe (+3) | O (-2) | 3, 2 | 1 | 2 | 3 | Fe₂O₃ |
| Cu (+1) | O (-2) | 1, 2 | 1 | 2 | 1 | Cu₂O |
| Al (+3) | SO₄ (-2) | 3, 2 | 1 | 2 | 3 | Al₂(SO₄)₃ |
| Na (+1) | OH (-1) | 1, 1 | 1 | 1 | 1 | NaOH |
| Ca (+2) | OH (-1) | 2, 1 | 1 | 1 | 2 | Ca(OH)₂ |

---

## Relationship to Existing Code

| Concept | Where it lives |
|---|---|
| `calculateAtom(vL, vR)` | `ChemicalHelpers.php` — the crossover/GCD math |
| `assignCharges()` | `Reactant.php` — sets `substance.charge` on each substance |
| `getSafeValencies()` | `Substance.php` — returns valencies (or polyatomic charge) |
| Net charge calculation | **Not yet implemented** — needs a `netCharge(): int` method on `Reactant` |
| Scaling atoms by group count | **Not yet implemented** — new logic in `AdditionOperator::operate()` |
| Duplicate element merge | **Partially in old code** (commented out) — needs to work on the full substances collection |
