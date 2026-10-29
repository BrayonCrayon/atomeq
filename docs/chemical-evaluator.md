# Chemical Evaluator — Design, Status & Roadmap

The single reference for the `app/ChemicalEvaluator` engine. It replaces these older docs:
`addition-operator-roadmap.md`, `addition-operator-bug-analysis.md`, `multi-substance-addition.md`,
`valency-compound-formation.md`, `all-non-metal-charge-assignment.md`,
`nonmetal_oxidation_state_engine_design.md` and `lewis-structure-o3-upgrade-guide.md`.
Finished work is removed from this doc. Use git history for the details.

> Status last checked against the code on **2026-10-28**. The test suite needs the `testDB` Postgres
> host, so run it through Sail (`./vendor/bin/sail test`). The last run of `tests/Feature/ChemicalEvaluator`
> had 7 failures (section 2).

---

## 1. How the engine fits together

```text
"2Na + Cl<sub>2</sub> ="
        ↓  Tokenizer        → tokens in Reverse Polish Notation (shunting-yard)
        ↓  Evaluator        → walks the RPN stack
        ├─ AdditionOperator (BinaryOperator) → builds a compound from two Reactants
        └─ ReactionOperator (UnaryOperator)  → turns reactants into products
```

| Class | Role |
|---|---|
| `Tokenizer` | Splits the equation into operands/operators and orders them as RPN |
| `Evaluator` | Pops operands for each operator; handles both binary and unary operators |
| `Reactant` | One formula with a coefficient: parses substances, assigns charges, builds Lewis structures |
| `Substance` | One element (or polyatomic ion) with its atom count, charge, ion charge, valencies and an `isIsomer` flag |
| `ChemicalHelpers` | `calculateAtom()` (criss-cross/GCD), `valencyLookup()`, `electronegativeLookup()`, connectivity table |
| `LewisService`, `Bond`, `BondedElement` | Lewis structure builder (sections 6.1 and 6.4) |
| `NoReactionOperand` | Operand whose string is "No Reaction" |

Data in the DB: `elements` (`valence`, `electronegativity`, `is_diatomic`, `activity_rank`),
`element_valencies` (multiple valencies, one flagged `is_default`), and `polyatomic_ions` (symbol and charge).

---

## 2. Status at a glance

| Area | Status | Notes |
|---|---|---|
| Tokenizer/RPN/Evaluator, parsing, polyatomic ions, DB columns, signed charges, non-metal charge rules, unary `ReactionOperator`, cation-first ordering | ✅ Done | |
| Lewis structure, including capacity-checked bond upgrading | ✅ Done | O₃ stops at score 2; confirm with a test run. Open edge cases in section 6.3 |
| Isomer parsing (one element with a subscript, e.g. H₂, O₂) | ✅ Done | Section 6.5 |
| Lewis coverage beyond the current tests | 🟡 In progress | H₂ passes; NH₄⁺ test has wrong expectations (section 6.3) |
| Activity-series gate | 🟡 Regressed | Logic is commented out in `ReactionOperator::operate()`; tests still expect it |
| Neutral-compound addition (`2CO + O₂`) | ❌ Missing | Section 4.3 |
| Molecular ordering (`N₂ + H₂` → `NH₃`, not `H₃N`) | ❌ Missing | Section 4.3 |
| Valency notation in input (`Fe(III)`) | ❌ Missing | Section 5.2 |
| Diatomic enforcement on lone products | ❌ Missing | Section 5.1 |
| Single-replacement displacement | ❌ Missing | Section 5.1 |
| Oxidation states from the Lewis structure | ❌ Missing | Section 7 |

Failing tests in the last run: NF₂Cl charges (P gets −3 instead of +3), three
`EvaluatorTest` cases (section 4.3) and three `ReactionOperatorTest` cases (activity gate, section 5.1).

---

## 3. Core concepts (keep these separate)

| Term | Meaning | S in H₂S |
|---|---|---|
| **Valence electrons** | Electrons in the outer shell | 6 |
| **Valency** | How many bonds an atom commonly forms | 2 |
| **Oxidation state** | Charge after giving *all* bonding electrons to the more electronegative atom | −2 |
| **Formal charge** | Charge after splitting bonding electrons *evenly* | 0 |
| **Molecular net charge** | Charge of the whole species (`Reactant::$netCharge`) | 0 |

- Formal charge = valence electrons − nonbonding electrons − bonds (bonding electrons ÷ 2).
- Oxidation state = valence electrons − (lone-pair electrons + bonding electrons won by electronegativity).
- Bonding electrons = bond order × 2.

`Substance::$charge` currently mixes "ionic charge" and "oxidation state". Splitting it into a
separate `oxidationState` is a planned cleanup once section 7 exists.

---

## 4. Charges and the AdditionOperator

### 4.1 Charge assignment (current behaviour)

`Reactant::assignCharges()` runs on construction:

1. Lone substance or lone polyatomic ion: no charge assigned.
2. Element + polyatomic ion: the element takes the opposite of the ion's DB charge.
3. Otherwise `calculateCharges()` walks adjacent pairs. With metals present it uses the default
   valency; without metals it uses the first entry of `Reactant::oxidationStates`. The lower
   electronegativity becomes positive and the higher becomes negative.
4. All non-metals: `nonMetalSpecific()` hard-sets H = +1, F/halogens = −1 and O = −2, then
   solves C and P (`NET_NEUTRAL_CHARGE`) from the balance so the net charge is 0.

Known limits:
- The pairwise pass can't handle a "middle" element that isn't C or P. Step 4 only rescues C and P.
- `oxidationStates[$symbol][0]` picks a value from a list instead of deriving it. Section 7
  replaces this with real oxidation states from the Lewis structure. PBr₃S shows why: knowing
  Br = −1 leaves P + S = +3, which has many solutions, so the structure is needed to pick P = +5, S = −2.

### 4.2 Crossover rule (criss-cross at group level)

Each reactant acts as one super-ion whose valency is its net charge (`getChargeValency()`):

- left count = |right net charge| ÷ gcd
- right count = |left net charge| ÷ gcd

Scale every substance's atom count by its side's count, merge duplicate elements, then sort
cation first. This is `calculateAtom()` plus `setAtomCount()` plus `consolidateSubstances()`.

| Left | Right | Charges | Result |
|---|---|---|---|
| Na | Cl | +1, −1 | NaCl |
| Ca | Cl | +2, −1 | CaCl₂ |
| Fe (default) | O | +2, −2 | FeO |
| Fe (III) | O | +3, −2 | Fe₂O₃ |
| Cu (default) | O | +1, −2 | Cu₂O (also for `O + Cu`) |
| Al | SO₄ | +3, −2 | Al₂(SO₄)₃ |
| Ca | OH | +2, −1 | Ca(OH)₂ |

### 4.3 Open problems (EvaluatorTest)

| Test | Expected | Problem | Direction |
|---|---|---|---|
| `3Na + 3H + 3C + 2O₂ =` | `3NaHCO₃` | The chain builds NaHC first; its net charge decides the O count. The old ±1 root cause is fixed, so re-check what the chain produces now that charges are signed magnitudes | Trace each `+` step with the current charges |
| `2CO + O₂ =` | `2CO₂` | CO's net charge is 0, so `calculateAtom(2, 0)` sets O₂'s atom count to 0 | Neutral guard: skip the crossover and add (right atoms × right coefficient ÷ left coefficient) of the shared element to each left molecule |
| `N₂ + H₂ ->` | `NH₃` | Sorting by electronegativity puts H (2.20) before N (3.04) | A molecular (non-metal + non-metal) ordering rule, e.g. keep input order or follow the IUPAC N-before-H convention |

Other rules:
- Adding a neutral compound to a neutral compound is a reaction, not formula-building. It belongs in `ReactionOperator`.
- Two cations or two anions can't form a neutral compound, so the result should be a `NoReactionOperand`.

---

## 5. Reactions and valency

### 5.1 Single replacement (`A + BC -> AC + B`)

1. Identify the lone element A and the compound BC.
2. **Activity gate:** if A's `activity_rank` ≤ B's, return a `NoReactionOperand`. This logic exists but is
   commented out in `ReactionOperator::operate()`, while the `ReactionOperatorTest` cases still expect it.
3. A replaces the element in BC that has the same polarity (cation for cation).
4. Criss-cross A with C to form AC.
5. **Diatomic rule:** if the displaced B is in HNFOClBrI (`is_diatomic`) and stands alone, write it as B₂.
6. Return `AC + B`.

Example: `Mg + CuO`. Mg outranks Cu, so Mg (+2) with O (−2) gives MgO, and the result is `MgO + Cu`.

Inferring a metal's valency from an existing compound (FeCl₃ means Fe = +3) is needed at step 3.

### 5.2 Multiple valencies

- With no notation, use the `is_default` valency (Fe → +2, Cu → +1). Ideally also flag that other compounds exist.
- With notation such as `Fe(III)`, force that valency. The parser and `Tokenizer` don't support this yet.

---

## 6. Lewis structures (`LewisService`)

### 6.1 Pipeline

| Step | Where |
|---|---|
| Count valence electrons (minus ion charge) | `calculateTotalValenceElectrons()` |
| Choose a central atom (lowest EN, never halogens, never H unless the reactant is an isomer, skipping atoms already tried) | `assignCentralAtom()` |
| Build connectivity from bond capacity + `FUNCTIONAL_CONNECTIVITY`; return false if an atom can't be placed | `setupConnectivity()` |
| Single bonds for every connection | `assignDefaultBonds()` |
| Fill outer atoms to an octet; leftovers go to the central atom | `assignOutsideBondsLonePairs()` |
| Formal charges | `calculateFormalCharges()` |
| Upgrade bonds while charges are unfavourable, only where the outer atom has a lone pair and the inner atom has room (8, or 12 for `EXPANDED_OCTET_ATOMS`); roll back (DeepCopy) once the score stops strictly improving | `upgradeBonds()` + `canUpgrade()` + loop in `Reactant::lewisStructure()` |
| Try every other central atom (recursion); results are keyed by central element | `Reactant::lewisStructure()` |

Bond order is never looked up. It's 1 plus the number of lone pairs moved into that bond.

### 6.2 Ranking candidate structures

1. Correct total electron count (hard rule).
2. **Octets respected:** H = 2, period-2 atoms ≤ 8, and only `EXPANDED_OCTET_ATOMS` may go past 8 (10–12).
3. Minimise formal charges (`formalChargeScore`).
4. Put negative formal charge on the more electronegative atom.
5. Also try alternatives: other central atoms, other double-bond placements (resonance), and isomers.

**Octet first, formal charge second.** Formal charge only decides between structures that are already valid.

### 6.3 Invariants and open edge cases

Why the upgrade loop is safe (checked by reading the code):
- Every non-central atom is the outer atom of exactly one bond, the one to its host, so `upgradeBonds()` always finds a bond. Atoms with charge only on the central atom (NH₄⁺) just give an empty list.
- The loop ends because the score is a non-negative integer that must strictly drop on each pass, or the loop rolls back.
- An upgrade never leaves the central atom with fewer spare pairs, so if `assignOutsideBondsLonePairs()` covers every outer atom on the first pass, it keeps covering them.

Open:
- **NH₄⁺ test:** it expects `bonds` to be empty. A correct result is four N–H single bonds, N = +1, score 1.
- **Odd-electron species** (NO₂, 17 e⁻): `totalValenceElectrons / 2` gives a float that is forced into the int `lonePairs`.
- **Electron-deficient species:** `assignOutsideBondsLonePairs()` skips an outer atom the central atom can't cover, and that atom keeps its lone pairs from the previous pass.
- **H₂ test name:** the test passes (one H–H single bond, score 0) but is still named "hydrogen dioxide".
- **Upgrade target choice:** only the outer atom's bond to its host is upgraded. Resonance and other placements (section 6.2, step 5) aren't explored yet.

### 6.4 Bonds

- `Bond` links an `outsideElement` (the outer atom) to an `insideElement` (its host) with a `level`
  (bond order, default 1). The constructor adds the bond to both elements' `connections`.
- `BondedElement` derives its numbers from those connections: `bondedPairs` is the sum of the bond
  levels, `loneElectrons` is `lonePairs × 2`, and `totalElectrons` is lone electrons plus `bondedPairs × 2`.
- `Bond::toArray()` still uses the old keys `leftElement` (outside) and `rightElement` (inside). Rename them
  to match the properties when the test expectations are updated.

### 6.5 Isomers (single-element molecules)

An "isomer" here means a reactant made of one element with a subscript and nothing else (H₂, O₂, N₂).

- `Reactant::parseReactant()` detects it with `$isomerPattern` and sets `Substance::$isIsomer`.
- For an isomer, single-element polyatomic ions (peroxide `O2`) are left out of the polyatomic
  pattern, so `O₂` on its own is plain oxygen. Inside a compound, `H₂O₂` still reads O₂ as peroxide.
- `assignCentralAtom()` allows H as the central atom only for isomers, and `FUNCTIONAL_CONNECTIVITY`
  now has H–H and C–C entries (both `possible`).

---

## 7. Oxidation states from the Lewis structure (next phase)

Once the Lewis structures are reliable:

1. Each atom starts with its lone-pair electrons.
2. For each bond, bonding electrons = order × 2. The more electronegative atom gets all of them; equal electronegativity splits them evenly.
3. Oxidation state = valence electrons − assigned electrons.
4. Check that the sum (times atom counts) equals the molecular net charge.

Worked example for PBr₃S (P=S plus three P–Br; Br > S > P):

| Atom | Assigned electrons | Oxidation state |
|---|---|---|
| Br | 6 lone + 2 bond = 8 | 7 − 8 = −1 (×3) |
| S | 4 lone + 4 bond = 8 | 6 − 8 = −2 |
| P | 0 | 5 − 0 = +5 |
| Sum | | +5 − 2 − 3 = 0 ✓ |

After that, `Reactant::oxidationStates` becomes a validation list only, and `assignCharges()` for
non-metals can come from this calculation.

---

## 8. Suggested build order

1. Fix the NH₄⁺ Lewis test expectations (section 6.3).
2. Widen Lewis coverage (H₂ is done): HCl, NH₃, CH₄, CO₂, then OH⁻, H₃O⁺, then O₂ and N₂, then PCl₅ and SF₆.
3. Calculate oxidation states from the structure (section 7) and feed them into charge assignment.
4. Open AdditionOperator problems: the neutral guard, molecular ordering, and re-checking NaHCO₃ (section 4.3).
5. Restore the activity gate, then add the diatomic rule and full single replacement (section 5.1).
6. `Fe(III)` valency notation (section 5.2).
