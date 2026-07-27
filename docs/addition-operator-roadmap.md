# AdditionOperator Implementation Roadmap

Gap analysis comparing `AdditonOperator.md` spec against the current codebase, with ordered actionable steps.

---

## Current State

| Component | Status |
|-----------|--------|
| Tokenizer / Evaluator (RPN stack) | ✅ Done |
| `Reactant` + `Substance` parsing (symbols, coefficients, subscripts) | ✅ Done |
| `calculateAtom` criss-cross with GCD reduction | ✅ Done |
| Multi-valency in DB (`element_valencies` table) | ✅ Done |
| `electronegativity` field on `elements` table | ✅ Done |
| `is_diatomic` field on `elements` | ✅ Done |
| `activity_rank` field on `elements` | ✅ Done |
| Polyatomic ion interception | ✅ Done |
| Electronegativity-based charge assignment | ✅ Done |
| Variable valency for transition metals | ❌ Missing |
| Activity series feasibility check | ❌ Missing |
| Diatomic enforcement on ejected products | ❌ Missing |
| Full single-replacement reaction logic | ❌ Missing |

---

## Step 1 — Add missing DB columns to `elements` ✅ Completed (2026-07-14)

Two fields from the spec are completely absent:

- `is_diatomic` (boolean, default `false`) — required for the HNFOICBr rule
- `activity_rank` (integer, nullable) — required for the activity series feasibility check

**Completed:**
- ✅ Migration adding both columns (`is_diatomic`, `activity_rank`)
- ✅ Follow-up migration populating `is_diatomic = true` → H, N, F, O, I, Cl, Br
- ✅ Follow-up migration populating `activity_rank` values (Li=50, Na=45, Mg=40, Al=35, Zn=25, Fe=20, Ni=18, Sn=15, Pb=12, H=10, Cu=5, Ag=3, Au=1)

- ✅ `is_diatomic` and `activity_rank` added to `Element::$fillable`

---

## Step 2 — Polyatomic ion interception in `Reactant` parsing (Rule II) ✅ Completed

The tokenizer previously split `MgSO4` into `Mg`, `S`, `O4` individually. The spec required intercepting known polyatomic clusters (SO4, NH4, OH, NO3, etc.) as a single charged unit **before** element-by-element parsing.

**Completed:**
- ✅ `polyatomic_ions` DB table + seeder with all known ions
- ✅ `Reactant::parseReactant()` now queries `PolyatomicIon` model to build a dynamic regex pattern
- ✅ `preg_split()` with `PREG_SPLIT_DELIM_CAPTURE` splits the substance string on polyatomic matches, capturing them as delimiters
- ✅ Matched polyatomic parts are passed to `new Substance($part, true)` (flagged as polyatomic); remaining parts are parsed element-by-element as before
- ✅ Subscript normalization (`\d+` → `<sub>$1</sub>`) applied to ion symbols before pattern matching

**Files touched:** `Reactant.php`, `Substance.php`, `PolyatomicIon.php` (model), migration + seeder

---

## Step 3 — Charge assignment via electronegativity (Rule I) ✅ Completed

**Completed:**
- ✅ `?float $charge` property on `Substance` (null = neutral/unknown)
- ✅ Polyatomic ion charge assigned from `polyatomic_ions.charge` DB column in `parsePolyatomicIon()` — electronegativity comparison skipped for these
- ✅ `Reactant::assignCharges()` method handles all three cases:
  - Single substance or lone polyatomic ion → skip (neutral, no charge needed)
  - Regular element + polyatomic ion → regular element gets the inverted sign of the polyatomic's DB charge
  - Two regular elements → compare `electronegativity` from DB; lower EN → `+1` (cation), higher EN → `-1` (anion)
- ✅ `assignCharges()` called from `Reactant::__construct()` after `parseReactant()`

**Files touched:** `Reactant.php`, `Substance.php`

---

## Step 4 — Variable valency for transition metals (Rule III)

`AdditionOperator` receives two separate `Reactant`s (e.g., `Fe` and `Cl`) and builds the product — it never reads an existing compound. Charges are already assigned by Step 3's `assignCharges()`. So all that's needed is: when a transition metal has multiple valencies, pick the default one rather than blindly using `->first()`.

**Action:**
1. ✅ Add an `is_default` boolean column (default `false`) to `element_valencies` — the creation migration sets `is_default = true` for all single-valency elements migrated from the old `elements.valency` column; the population migration marks the first valency in each array as default (e.g., Fe → +2). `Valency::$fillable` and cast updated accordingly.
2. ✅ In `AdditionOperator::operate()`, replaced `valencies->first()` with `valencies->where('is_default', true)->first()` for both the left and right substances before passing to `calculateAtom()`.

> **Note:** Deducing a transition metal's valency *from an existing compound* (e.g., reading `FeCl3` to infer Fe = +3 from Cl × 3) is a **Step 8 concern**, needed when `ReactionOperator` parses a compound reactant in a single-replacement reaction.

---

## Step 5 — Cation-before-anion ordering in `AdditionOperator` output ✅ Completed

The spec's Criss-Cross section states the cation (positive) always comes first in the product formula. Previously, output order was left-then-right — tests passed accidentally because the cation was always passed as `$left`.

**Completed:**
- ✅ `AdditionOperator::operate()` now queries both elements' `electronegativity` from DB and swaps the substances if the left has higher EN (i.e., is the anion), ensuring the cation is always index 0 before criss-cross.
- ✅ Added failing test: `O + Cu` (anion-first input order) → asserts output is `Cu<sub>2</sub>O`.

---

## Step 6 — Activity series gate in `ReactionOperator` (Rule IV)

`ReactionOperator::operate()` currently just returns `$right` unconditionally. The spec requires: for a single-replacement reaction `A + BC ->`, compare `A.activity_rank` vs `B.activity_rank`. If A is weaker (lower rank) than B, return `"No Reaction"`.

**Action:**
1. Identify which reactant is the lone element (A) and which is the compound (BC)
2. Look up both elements' `activity_rank`
3. If `A.activity_rank <= B.activity_rank`, return a `NoReactionOperand` (a simple `Operand` subclass whose `__toString()` returns `"No Reaction"`)
4. Otherwise, proceed with the displacement

**Files to touch:** `ReactionOperator.php`, new `NoReactionOperand.php`

---

## Step 7 — Diatomic enforcement on ejected products (Rule V)

When a diatomic element (H, N, F, O, I, Cl, Br) is ejected as a lone product, the output must have subscript 2. Currently `Substance::__toString()` has no awareness of the `is_diatomic` flag.

**Action:** In `Substance::__toString()`, if `is_diatomic` is `true` on this element's DB record and `$this->atom` is 1, set `$this->atom = 2` before formatting. Enforce this only when the substance is a lone product (i.e., its parent `Reactant` has exactly one substance).

**Files to touch:** `Substance.php` (or enforce at the `ReactionOperator` level when constructing the ejected product)

---

## Step 8 — Full single-replacement reaction in `ReactionOperator`

After Steps 3–7 are in place, implement the actual displacement logic. Right now `ReactionOperator` performs no chemistry.

**Algorithm:**
1. Identify lone element A and compound BC
2. Run the activity series check (Step 6) — return early if "No Reaction"
3. Determine which element in BC shares polarity intent with A (both want to be cation, or both want to be anion)
4. Displace that element: A takes its place, the displaced element becomes the lone product
5. Apply criss-cross (`calculateAtom`) to form the new compound AC
6. Apply the diatomic rule (Step 7) to the displaced B if applicable
7. Return both products as a formatted string: `AC + B2` (or `AC + B`)

**Files to touch:** `ReactionOperator.php`

---

## Recommended build order

```
Step 1  →  Step 2  →  Step 3  →  Step 4  →  Step 5
                                                  ↓
                                             Step 6  →  Step 7  →  Step 8
```

Steps 1–5 lay the data and charge foundations. Steps 6–8 are the final chemistry engine layer that depends on all prior steps being in place.
