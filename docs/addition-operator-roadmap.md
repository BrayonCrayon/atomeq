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
| Electronegativity-based charge assignment | ❌ Missing |
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

## Step 3 — Charge assignment via electronegativity (Rule I)

`Substance` and `AdditionOperator` currently have no concept of cation/anion polarity. The spec requires: within a compound, higher electronegativity → negative (anion), lower → positive (cation).

**Action:**
1. Add a `?int $charge` property to `Substance` (null = unknown, positive int = cation, negative int = anion)
2. In `Reactant::parseReactant()`, after parsing all substances, look up each element's `electronegativity` and assign `+` to the lower one, `-` to the higher one
3. Polyatomic ions already have a known charge from Step 2 — skip electronegativity for those

**Files to touch:** `Substance.php`, `Reactant.php`

---

## Step 4 — Variable valency for transition metals (Rule III)

`AdditionOperator` currently blindly takes `valencies->first()`. The spec requires:

- **Inside a compound** (e.g., `FeCl3`): deduce the metal's charge from the non-metal's known charge × its subscript, then invert. FeCl3 → Cl is always -1, × 3 = -3, so Fe must be +3.
- **Lone transition metal**: fall back to a `default_oxidation_state` column (e.g., Fe=+2, Cu=+2).

**Action:**
1. Add a `default_oxidation_state` nullable integer column to `elements` table and `Element::$fillable`
2. Add a `deduceValency(Substance $partner): int` method to `Substance` that performs the Rule III calculation when the element has multiple valencies
3. Use this in `AdditionOperator` instead of `->first()`

**Files to touch:** New migration, `Element.php`, `Substance.php`, `AdditionOperator.php`

---

## Step 5 — Cation-before-anion ordering in `AdditionOperator` output

The spec's Criss-Cross section states the cation (positive) always comes first in the product formula. Currently output order is left-then-right, which may not respect this.

**Action:** In `AdditionOperator::operate()`, after charges are assigned (Step 3), sort the two substances so the cation is index 0 and anion is index 1 before building the result `Reactant`.

**Files to touch:** `AdditionOperator.php`

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
