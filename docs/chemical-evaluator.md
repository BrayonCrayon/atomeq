# Chemical Evaluator — Design, Status & Roadmap

The single reference for the `app/ChemicalEvaluator` engine. It replaces these older docs:
`addition-operator-roadmap.md`, `addition-operator-bug-analysis.md`, `multi-substance-addition.md`,
`valency-compound-formation.md`, `all-non-metal-charge-assignment.md`,
`nonmetal_oxidation_state_engine_design.md` and `lewis-structure-o3-upgrade-guide.md`.

> Status last checked against the code on **2026-10-28**. The test suite needs the `testDB` Postgres
> host, so "status" below is read from the code, not from a test run.

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
| `Substance` | One element (or polyatomic ion) with its atom count, charge, ion charge and valencies |
| `ChemicalHelpers` | `calculateAtom()` (criss-cross/GCD), `valencyLookup()`, `electronegativeLookup()`, connectivity table |
| `LewisService`, `Bond`, `BondedElement` | Lewis structure builder (section 6) |
| `NoReactionOperand` | Operand whose string is "No Reaction" |

Data in the DB: `elements` (`valence`, `electronegativity`, `is_diatomic`, `activity_rank`),
`element_valencies` (multiple valencies, one flagged `is_default`), and `polyatomic_ions` (symbol and charge).

---

## 2. Status at a glance

| Area | Status | Notes |
|---|---|---|
| Tokenizer, RPN, Evaluator | ✅ Done | |
| Parsing (coefficients, subscripts, `<sup>` ion charges) | ✅ Done | `Substance::$ionCharge` |
| Polyatomic ion interception | ✅ Done | Dynamic regex built from `polyatomic_ions` |
| DB: `is_diatomic`, `activity_rank`, `element_valencies.is_default` | ✅ Done | |
| Charges as signed magnitudes (not ±1) | ✅ Done | `Reactant::calculateCharges()` |
| All-non-metal charge rules (H +1, halogens −1, O −2, C/P balance) | ✅ Done | `Reactant::nonMetalSpecific()`; HCCl₃ → C = +2 |
| `UnaryOperator` + `ReactionOperator` as unary | ✅ Done | |
| Cation-before-anion ordering | ✅ Done | `consolidateSubstances()` sorts by electronegativity |
| Lewis structure: electrons, central atom, connectivity, single bonds, lone pairs, formal charges | ✅ Done | |
| Lewis structure: bond upgrading | 🟡 In progress | O₃ over-upgrades (section 6.3) |
| Activity-series gate | 🟡 Regressed | Logic is commented out in `ReactionOperator::operate()`; tests still expect it |
| Neutral-compound addition (`2CO + O₂`) | ❌ Missing | Section 4.3 |
| Molecular ordering (`N₂ + H₂` → `NH₃`, not `H₃N`) | ❌ Missing | Section 4.3 |
| Valency notation in input (`Fe(III)`) | ❌ Missing | Section 5.2 |
| Diatomic enforcement on lone products | ❌ Missing | Section 5.1 |
| Single-replacement displacement | ❌ Missing | Section 5.1 |
| Oxidation states from the Lewis structure | ❌ Missing | Section 7 |

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
| Choose a central atom (lowest EN, never H/halogens, skipping atoms already tried) | `assignCentralAtom()` |
| Build connectivity from bond capacity + `FUNCTIONAL_CONNECTIVITY`; return false if an atom can't be placed | `setupConnectivity()` |
| Single bonds for every connection | `assignDefaultBonds()` |
| Fill outer atoms to an octet; leftovers go to the central atom | `assignOutsideBondsLonePairs()` |
| Formal charges | `calculateFormalCharges()` |
| Upgrade bonds while charges are unfavourable; roll back (DeepCopy) if the score stops improving | `upgradeBonds()` + loop in `Reactant::lewisStructure()` |
| Try every other central atom (recursion); results are keyed by central element | `Reactant::lewisStructure()` |

Bond order is never looked up. It's 1 plus the number of lone pairs moved into that bond.

### 6.2 Ranking candidate structures

1. Correct total electron count (hard rule).
2. **Octets respected:** H = 2, period-2 atoms ≤ 8, and only `EXPANDED_OCTET_ATOMS` may go past 8 (10–12).
3. Minimise formal charges (`formalChargeScore`).
4. Put negative formal charge on the more electronegative atom.
5. Also try alternatives: other central atoms, other double-bond placements (resonance), and isomers.

**Octet first, formal charge second.** Formal charge only decides between structures that are already valid.

### 6.3 Current bug: O₃ over-upgrades

The O₃ test expects a final `formalChargeScore` of **2** but gets **0**.

O₃ has 18 valence electrons, and its skeleton is `O₁ – O₀ – O₂`:

| Step | Bonds (O₁–O₀, O₀–O₂) | Lone pairs (O₁, O₀, O₂) | Formal charges | Score | e⁻ around O₀ |
|---|---|---|---|---|---|
| Initial | 1, 1 | 3, 1, 3 | −1, +2, −1 | 4 | 6 |
| Upgrade #1 | 2, 1 | 2, 1, 3 | 0, +1, −1 | 2 | 8 ✅ |
| Upgrade #2 | 2, 2 | 2, 1, 2 | 0, 0, 0 | 0 | **10 ❌** |

Nothing in the loop checks whether a structure is allowed; it only checks the score. The second
upgrade lowers the score but gives a period-2 oxygen 10 electrons. SO₂, H₂SO₄ and PBr₃S pass only
because their over-filled atom (S or P) is allowed to expand.

**What to build:**

1. **Electron-capacity check.** Electrons around an atom = lone electrons + 2 × `bondedPairs`. The maximum
   is 2 for H, 8 normally, and more for `EXPANDED_OCTET_ATOMS`. Reuse the list `bondCapacity()` already uses.
2. **Where it runs.** Preferably skip candidate bonds that would break capacity *before* upgrading. The
   alternative is to upgrade, check, and roll back with the existing DeepCopy.
3. **A "no valid upgrade left" signal.** Without it, `hasUnfavorableCharges()` stays true and the loop
   never ends.
4. **Better target choice.** A good upgrade joins an atom with a negative charge and a lone pair to give
   to a neighbour that is positive or short of electrons, without breaking capacity. O₃'s second
   step fails only that last condition, which is why it should stop at a score of 2.

**Latent bugs in the same area:**
- `upgradeBonds()` assumes a charged non-central atom exists. With NH₄⁺ the charge sits on the central atom, the lookup returns null, and the next line crashes.
- The H filter checks `rightElement`, which is the host. Check "the other atom in the bond" instead.
- A charged species can never reach a score of 0, so only the score rollback ends its loop.
- `assignOutsideBondsLonePairs()` silently skips an atom that the central atom can't cover.

**Suggested order:** add a test pinning O₃'s central atom at 8 electrons, then the capacity check,
then the no-upgrade signal and loop exit, then smarter targets. Add NH₄⁺ as a test for the null case.

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

1. Fix Lewis bond upgrading (section 6.3) and get the Lewis tests green.
2. Widen Lewis coverage: H₂, HCl, NH₃, CH₄, CO₂, then NH₄⁺, OH⁻, H₃O⁺, then O₂ and N₂, then PCl₅ and SF₆.
3. Calculate oxidation states from the structure (section 7) and feed them into charge assignment.
4. Open AdditionOperator problems: the neutral guard, molecular ordering, and re-checking NaHCO₃ (section 4.3).
5. Restore the activity gate, then add the diatomic rule and full single replacement (section 5.1).
6. `Fe(III)` valency notation (section 5.2).
