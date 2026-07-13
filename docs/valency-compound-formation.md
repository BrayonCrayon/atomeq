# Valency-Based Compound Formation

## The Problem

Some elements have multiple valid valencies (e.g. Fe: 2, 3). When combining reactants,
there is no single "correct" valency without knowing reaction conditions. Both FeO and
Fe₂O₃ are valid products of Fe + O — they are different compounds.

## Algorithm: Cross-Multiply Method

Given element A with valency `vₐ` and element B with valency `v_b`, the atom ratio is:

```
atoms of A = v_b / GCD(vₐ, v_b)
atoms of B = vₐ / GCD(vₐ, v_b)
```

### Example: Fe (valencies 2, 3) + O (valency 2)

| Fe valency | O valency | GCD | Fe atoms | O atoms | Formula |
|---|---|---|---|---|---|
| 2 | 2 | 2 | 1 | 1 | FeO |
| 3 | 2 | 1 | 2 | 3 | Fe₂O₃ |

### Example: Cu (valencies 1, 2) + O (valency 2)

| Cu valency | O valency | GCD | Cu atoms | O atoms | Formula |
|---|---|---|---|---|---|
| 1 | 2 | 1 | 2 | 1 | Cu₂O |
| 2 | 2 | 2 | 1 | 1 | CuO |

### Example: Single Displacement — Mg + CuO -> MgO + Cu

This is a **single displacement reaction** (pattern: `A + BC -> AC + B`).

**Step 1 — Identify free element vs compound in reactants:**
- `Mg` is a free metal (valency 2)
- `CuO` is a compound: Cu (valency 2) + O (valency 2)

**Step 2 — Displacement check (activity series):**
- Mg is more reactive than Cu → Mg can displace Cu from CuO

**Step 3 — Form new compound using cross-multiply:**
- Free metal Mg (val 2) + extracted non-metal O (val 2)

| Mg valency | O valency | GCD | Mg atoms | O atoms | Formula |
|---|---|---|---|---|---|
| 2 | 2 | 2 | 1 | 1 | MgO |

**Step 4 — Displaced element becomes free product:**
- Cu is released as elemental copper

**Atom balance (coefficients all = 1):**

| Side | Mg | Cu | O |
|---|---|---|---|
| Reactants (Mg + CuO) | 1 | 1 | 1 |
| Products (MgO + Cu) | 1 | 1 | 1 |

---

## Recommended Approach

**Enumerate all valid combinations and require disambiguation via notation.**

- If the user writes `Fe + O` with no valency specified → default to lowest valency, produce one compound, flag that others exist
- If the user writes `Fe(III) + O` → use valency 3, produce Fe₂O₃

This avoids silently producing the "wrong" compound when a student expects a specific one.

## What Needs Building

1. **Update `valencyLookup()`** (or add a new helper) to return all valencies from the
   `element_valencies` table for elements that have them, falling back to `elements.valency`
   for elements with only one.

2. **Valency notation in the parser** — `Reactant.php` / `Tokenizer.php` need to handle
   `Fe(II)` syntax, extracting element "Fe" with a forced valency of 2.

3. **Implement `calculateValency()`** in `ChemicalHelpers.php` using the cross-multiply/GCD
   method above.

4. **Update `AdditionOperator`** to use valencies when determining atom counts, rather than
   just aggregating substances as-is.

## Key Files

| File | Role |
|---|---|
| `app/ChemicalEvaluator/ChemicalHelpers.php` | `valencyLookup()` and `calculateValency()` stub |
| `app/ChemicalEvaluator/Substance.php` | Per-element representation, calls `valencyLookup()` |
| `app/ChemicalEvaluator/Reactant.php` | Parses compound strings, creates `Substance` objects |
| `app/ChemicalEvaluator/Tokenizer.php` | Tokenizes equation strings into RPN |
| `app/ChemicalEvaluator/Evaluator.php` | Evaluates RPN stack |
| `app/ChemicalEvaluator/AdditionOperator.php` | Combines two reactants (needs valency logic) |
| `database/migrations/2026_06_22_202057_add_multiple_element_valencies.php` | Populates `element_valencies` table |
