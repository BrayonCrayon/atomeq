# Chemical Evaluator Logic Matrix & Architectural Specification

This document outlines the core algorithmic rules, data structures, and logical checks required to build a deterministic chemical reaction engine for single-replacement reactions without requiring a heavy molecular physics engine.

---

## 1. Database Schema Extensions

To support the evaluator logic, your elements/compounds database must store the following properties:

| Property | Data Type | Purpose | Example |
| :--- | :--- | :--- | :--- |
| `electronegativity` | Float (0.70 - 3.98) | Determines charge polarities in compounds. | `Cl = 3.16`, `H = 2.20` |
| `group_number` | Integer (1 - 18) | Identifies standard valency / ion intent. | `Mg = 2` (Group 2) |
| `element_type` | String/Enum | Categories: `Metal`, `Non-Metal`, `Transition-Metal` | `Fe = Transition-Metal` |
| `is_diatomic` | Boolean | Forces subscript `2` when isolated as a product. | `O = true`, `Al = false` |
| `activity_rank` | Integer (1 - N) | Higher number = more reactive (higher in series). | `Li = 50`, `Cu = 5` |

---

## 2. Step-by-Step Evaluator Logic Flow

When a reaction query is received (e.g., `A + BC`), the algorithm must execute the following sequential pipeline:

1. Parse & Match Polyatomic: Check if "BC" contains a known multi-atom ion (e.g., SO4).
2. Assign Reactant Polarities: Compare Electronegativity for B vs C inside the compound.
3. Determine Intended Role: Determine if the lone "A" wants to act as a positive (+) or negative (-) ion.
4. Thermodynamic Filter: Compare the activity_rank of A against its direct target (B).
5. Formula Math & Formatting: Balances charges, cross-multiplies valencies, and applies the Diatomic rule.

---

## 3. Core Logic Rules & Edge Cases

### Rule I: Charge Assignment via Electronegativity
When evaluating a binary compound (BC), the relative electronegativity determines the pseudo-charge roles:
* Higher Electronegativity = Assigned Negative (-) role (Anion).
* Lower Electronegativity = Assigned Positive (+) role (Cation).

Example: In HCl, Cl (3.16) is greater than H (2.20). Therefore, Cl is (-) and H is (+).

### Rule II: Polyatomic Ion Interception
Before splitting a compound by individual elements, your string parser must check a hardcoded lookup dictionary for polyatomic clusters. Treat these clusters as a single entity with a fixed charge.

Hardcoded Reference Map:
* `NH4` -> Charge: +1 (Ammonium)
* `H3O` -> Charge: +1 (Hydronium)
* `OH` -> Charge: -1 (Hydroxide)
* `NO3` -> Charge: -1 (Nitrate)
* `NO2` -> Charge: -1 (Nitrite)
* `CN` -> Charge: -1 (Cyanide)
* `SCN` -> Charge: -1 (Thiocyanate)
* `CH3COO` -> Charge: -1 (Acetate)
* `C2H3O2` -> Charge: -1 (Acetate - Alt Formula)
* `HCO3` -> Charge: -1 (Bicarbonate / Hydrogen Carbonate)
* `HSO4` -> Charge: -1 (Bisulfate / Hydrogen Sulfate)
* `HSO3` -> Charge: -1 (Bisulfite / Hydrogen Sulfite)
* `H2PO4` -> Charge: -1 (Dihydrogen Phosphate)
* `MnO4` -> Charge: -1 (Permanganate)
* `ClO` -> Charge: -1 (Hypochlorite)
* `ClO2` -> Charge: -1 (Chlorite)
* `ClO3` -> Charge: -1 (Chlorate)
* `ClO4` -> Charge: -1 (Perchlorate)
* `BrO` -> Charge: -1 (Hypobromite)
* `BrO2` -> Charge: -1 (Bromite)
* `BrO3` -> Charge: -1 (Bromate)
* `BrO4` -> Charge: -1 (Perbromate)
* `IO` -> Charge: -1 (Hypoiodite)
* `IO2` -> Charge: -1 (Iodite)
* `IO3` -> Charge: -1 (Iodate)
* `IO4` -> Charge: -1 (Periodate)
* `SO4` -> Charge: -2 (Sulfate)
* `SO3` -> Charge: -2 (Sulfite)
* `CO3` -> Charge: -2 (Carbonate)
* `CrO4` -> Charge: -2 (Chromate)
* `Cr2O7` -> Charge: -2 (Dichromate)
* `C2O4` -> Charge: -2 (Oxalate)
* `S2O3` -> Charge: -2 (Thiosulfate)
* `HPO4` -> Charge: -2 (Hydrogen Phosphate)
* `O2` -> Charge: -2 (Peroxide)
* `SiO3` -> Charge: -2 (Silicate)
* `PO4` -> Charge: -3 (Phosphate)
* `PO3` -> Charge: -3 (Phosphite)
* `AsO4` -> Charge: -3 (Arsenate)
* `BO3` -> Charge: -3 (Borate)



Application: Given MgSO4, the engine intercepts SO4 as a single (-2) block, deducing that Mg must be (+2) to balance it out perfectly.

### Rule III: Variable Valency (Transition Metals)
Transition metals (Groups 3 to 12) cannot use static database charges because their oxidation states change dynamically.
1. Inside a Reactant Compound: Deduce the metal's charge by multiplying the bounding non-metal/polyatomic ion's charge by its subscript, then invert it. (e.g., For FeCl3, Cl is always -1. 3 times -1 is -3. Therefore, Fe must be +3).
2. As an Isolated Invader: If a transition metal is the lone reactant, fallback to a database property like `default_oxidation_state` (e.g., Fe defaults to +2, Cu defaults to +2).

### Rule IV: The Activity Series (Thermodynamic Feasibility)
A reaction only occurs if the lone invader (A) is chemically "stronger" than the element it wants to replace (B) inside the compound.

Pseudo-code rule check:
if lone_element.activity_rank <= target_element.activity_rank:
return "No Reaction"

Example: Cu + MgO results in "No Reaction" because Copper is lower on the activity series than Magnesium.

### Rule V: Diatomic Enforcement ("HNFOICBr")
Isolated non-metals cannot exist as single atoms in a product string. If an element's `is_diatomic` flag is true and it is ejected as a lone product, the formatter must append a 2 subscript.
* Elements: H2, N2, F2, O2, I2, Cl2, Br2
* Example: 2Mg + 2HCl -> 2MgCl2 + H2 (never just "+ H").

---

## 4. Product Formula Formatting Logic (Criss-Cross Algorithm)

When a reaction passes the activity test, calculate the product compound subscripts by crossing the absolute values of their valencies:

Cation Charge: +X  crosses down to become Anion Subscript
Anion Charge:  -Y  crosses down to become Cation Subscript

Resulting Formula Structure: Cation_Y Anion_X

1. Reduce Subscripts: If Y and X share a common denominator (e.g., Mg2O2), divide both by the greatest common divisor to simplify it (MgO).
2. Omit Ones: If any resulting subscript is 1, drop it entirely from the final string output (e.g., MgCl1 becomes MgCl).
