# Non-Metal Oxidation State Engine — Design Notes

## Goal

Build a chemistry engine that can calculate **oxidation states** for compounds, especially compounds made entirely of nonmetals.

The important design decision is:

> Do not determine oxidation states by simply picking a value from a list of common oxidation states.

Instead, the engine should model the chemistry that defines an oxidation state:

```text
Chemical formula
      ↓
Lewis structure / molecular structure
      ↓
Bonds + bond orders + lone pairs
      ↓
Electronegativity determines who gets bonding electrons
      ↓
Count electrons assigned to each atom
      ↓
Calculate oxidation states
      ↓
Verify against molecular net charge
```

---

# 1. Important terminology

These three concepts must remain separate.

## Valence electrons

The number of electrons in an atom's outer shell.

Examples:

| Element | Valence electrons |
|---|---:|
| H | 1 |
| C | 4 |
| N | 5 |
| O | 6 |
| F | 7 |
| P | 5 |
| S | 6 |
| Cl | 7 |
| Br | 7 |
| I | 7 |

For this engine, **valence electrons** are important to calculating oxidation states.

## Valency

Roughly, how many bonds an atom commonly forms.

Example:

```text
S → commonly valency 2
```

Valency is **not** the same thing as oxidation state.

## Oxidation state

The formal charge-like number assigned to an atom after assigning bonding electrons according to electronegativity.

Example:

```text
S in H₂S → oxidation state -2
```

Therefore:

```text
Sulfur:
    valence electrons = 6
    common valency    = 2
    oxidation state   = -2
```

---

# 2. Where do bonding electrons come from?

We do not need a database containing the number of bonding electrons for every possible bond.

The number comes from the **bond order**.

| Bond | Bond order | Bonding electrons |
|---|---:|---:|
| `A — B` | 1 | 2 |
| `A = B` | 2 | 4 |
| `A ≡ B` | 3 | 6 |

General rule:

```text
bonding electrons = bond order × 2
```

So:

```text
single bond → 1 × 2 = 2
double bond → 2 × 2 = 4
triple bond → 3 × 2 = 6
```

The engine therefore needs to know the **molecular structure**, including bond order.

---

# 3. Oxidation-state electron assignment

For a bond between two different atoms:

1. Compare their electronegativities.
2. The more electronegative atom gets **all** of the bonding electrons for oxidation-state purposes.
3. The less electronegative atom gets none of those bonding electrons.

Example:

```text
P — S
```

Approximate electronegativities:

```text
P = 2.19
S = 2.58
```

Therefore:

```text
S > P
```

Sulfur receives the bonding electrons.

If the bond is:

```text
P = S
```

it contains 4 bonding electrons, and sulfur receives all 4.

If the atoms have equal electronegativity, split the bonding electrons equally.

This handles elemental molecules such as:

```text
H₂
N₂
O₂
F₂
Cl₂
Br₂
I₂
```

where each atom has oxidation state 0.

---

# 4. Lone pairs

An atom's nonbonding electrons (lone pairs) always remain assigned to that atom.

For example, sulfur in a P=S bond can be represented as:

```text
      ..
P  =  S
      ..
```

Two lone pairs = 4 electrons.

If sulfur also gets 4 electrons from the P=S double bond:

```text
lone-pair electrons   = 4
bonding electrons     = 4
total assigned        = 8
```

Sulfur has 6 valence electrons as a neutral atom:

```text
oxidation state = valence electrons - assigned electrons

                = 6 - 8

                = -2
```

Therefore:

```text
S = -2
```

---

# 5. Example: PBr₃S

A useful structure for this example is:

```text
          Br
          |
     Br — P = S
          |
          Br
```

This gives us:

```text
P-Br → single bond → 2 electrons
P-Br → single bond → 2 electrons
P-Br → single bond → 2 electrons
P=S  → double bond → 4 electrons
```

Approximate electronegativities:

```text
Br = 2.96
S  = 2.58
P  = 2.19
```

Therefore:

```text
Br > S > P
```

## Bromine

Br is more electronegative than P.

Each P-Br bond contains 2 electrons.

Br gets those bonding electrons.

Br also has 6 nonbonding electrons.

Therefore each Br is assigned:

```text
6 + 2 = 8 electrons
```

Neutral Br has 7 valence electrons:

```text
7 - 8 = -1
```

Therefore:

```text
Br = -1
```

There are three Br atoms:

```text
3 × -1 = -3
```

## Sulfur

S is more electronegative than P.

The P=S double bond contains 4 electrons.

S receives all 4.

S also has two lone pairs:

```text
2 × 2 = 4 electrons
```

Therefore S is assigned:

```text
4 + 4 = 8 electrons
```

Neutral S has 6 valence electrons:

```text
6 - 8 = -2
```

Therefore:

```text
S = -2
```

## Phosphorus

P is less electronegative than both Br and S.

Therefore P receives none of the bonding electrons for oxidation-state purposes.

Neutral P has 5 valence electrons:

```text
5 - 0 = +5
```

Therefore:

```text
P = +5
```

## Verify

```text
P  = +5
S  = -2
Br = -1
```

There are three bromines:

```text
(+5) + (-2) + 3(-1)

= +5 - 2 - 3

= 0
```

The molecule is neutral, so the result checks.

---

# 6. Why two unknowns cannot be solved from charge alone

For PBr₃S, if we only know that Br = -1:

```text
P + 3(-1) + S = 0
```

which gives:

```text
P + S = +3
```

That is not enough to uniquely determine P and S.

For example, mathematically:

```text
P = +5, S = -2 → +3
P = +4, S = -1 → +3
P = +3, S =  0 → +3
```

The engine therefore needs additional chemical information.

The missing information is the **bonding structure and electronegativity**.

This is why the engine should not simply choose an oxidation state from:

```php
'S' => [-2, -1, 0, 1, 2, 3, 4, 5, 6]
```

That list can be useful as a reference or validation list, but it should not be the primary calculation mechanism.

---

# 7. Proposed engine architecture

The current code has:

```text
Reactant
    ↓
Substance
    ↓
Element
```

Eventually, the engine should look more like:

```text
                CHEMICAL FORMULA
                       |
                       v
              ┌─────────────────┐
              │ Lewis Structure │
              │    Builder      │
              └────────┬────────┘
                       |
                       v
                 MOLECULAR GRAPH
                       |
                 ┌─────┴─────┐
                 ↓           ↓
              Bonds       Lone pairs
                 |
                 v
          Oxidation Calculator
                 |
                 v
          Oxidation States
                 |
                 v
          Net-charge validation
```

Element data should contain at least:

```text
symbol
valence electrons
electronegativity
```

The molecular structure should contain:

```text
atoms
bonds
bond orders
lone pairs
```

---

# 8. High-level oxidation-state pseudocode

```text
FUNCTION calculateOxidationStates(formula):

    1. Parse the formula

       Get all atoms and their quantities.

    2. Determine the molecular structure

       Figure out:
           - which atoms are bonded
           - bond order
           - lone pairs

    3. Create the molecular graph

       Example PBr₃S:

           P-Br  order 1
           P-Br  order 1
           P-Br  order 1
           P-S   order 2

    4. For every atom:

       assignedElectrons = lonePairElectrons

    5. For every bond:

       bondingElectrons = bondOrder × 2

       Get electronegativity of atom A.
       Get electronegativity of atom B.

       IF A is more electronegative:

           Give all bonding electrons to A.

       ELSE IF B is more electronegative:

           Give all bonding electrons to B.

       ELSE:

           Split bonding electrons equally.

    6. For every atom:

       oxidationState =
           valenceElectrons - assignedElectrons

    7. Add all oxidation states,
       accounting for atom counts/subscripts.

    8. Verify:

       Sum of oxidation states
       must equal molecular net charge.

    9. Return oxidation states.
```

---

# 9. Important implementation warning

The hardest part is **not** the final oxidation-state calculation.

The difficult part is:

> Given only a formula such as PBr₃S, how does the program determine the correct Lewis structure?

The formula tells us:

```text
1 P
3 Br
1 S
```

but does not directly tell us:

```text
P-Br
P-Br
P-Br
P=S
```

Therefore a serious formula-only engine needs a **Lewis structure generator**.

---

# 10. Lewis structure generation

The Lewis structure process can be broken down into high-school chemistry steps.

## Step 1 — Count total valence electrons

Look at every atom.

Take its number of valence electrons and multiply by how many atoms there are.

Example:

```text
PBr₃S

P  = 5
Br = 7 × 3 = 21
S  = 6
```

Total:

```text
5 + 21 + 6 = 32 electrons
```

So PBr₃S starts with:

```text
32 valence electrons
```

For an ion, adjust the total based on the ion's charge:

```text
negative charge → add electrons
positive charge → remove electrons
```

---

## Step 2 — Decide the basic atom arrangement

A simple starting rule:

> Hydrogen is almost always on the outside.

For many simple compounds, the least electronegative atom is a reasonable candidate for the central atom, with some important exceptions.

For PBr₃S, phosphorus is the central atom:

```text
        Br
        |
    Br— P —S
        |
        Br
```

At this stage, the bonds can initially be treated as single bonds.

---

## Step 3 — Connect the atoms with single bonds

Each single bond uses 2 electrons.

PBr₃S has four connections:

```text
P-Br
P-Br
P-Br
P-S
```

Four bonds use:

```text
4 × 2 = 8 electrons
```

We started with 32:

```text
32 - 8 = 24 electrons remaining
```

---

## Step 4 — Give outside atoms their octets

Start with the atoms on the outside.

Br normally wants 8 electrons.

Each Br already has 2 from its bond to P.

So each Br needs:

```text
8 - 2 = 6 more electrons
```

Three Br atoms need:

```text
3 × 6 = 18 electrons
```

We had 24 remaining:

```text
24 - 18 = 6 electrons remaining
```

Give the remaining 6 electrons to S:

```text
S gets 6 electrons
```

Now:

```text
Br → octet
Br → octet
Br → octet
S  → octet
```

But phosphorus only has the electrons from its four single bonds:

```text
4 bonds × 2 electrons = 8 electrons
```

Phosphorus has an octet.

---

## Step 5 — Check formal charges

At this point, we have a possible Lewis structure:

```text
          Br
          |
     Br — P — S
          |
          Br
```

with lone pairs around the outside atoms.

Now calculate formal charges.

The formal-charge formula is:

```text
formal charge =
valence electrons
- nonbonding electrons
- (bonding electrons / 2)
```

This is **different from oxidation state**.

For the initial all-single-bond structure, sulfur would have a formal charge that indicates the structure can be improved by changing the P-S bond.

This is where multiple bonds can become necessary.

---

# 11. Step 6 — Consider multiple bonds

If an atom has an unfavorable formal charge, move a lone pair from an adjacent atom into a bond.

For PBr₃S:

```text
S lone pair → P-S bond
```

This changes:

```text
P-S
```

into:

```text
P=S
```

Now sulfur has fewer nonbonding electrons but participates in a double bond.

The resulting structure is:

```text
          Br
          |
     Br — P = S
          |
          Br
```

This is a better Lewis representation for the compound.

---

# 12. Step 7 — Recalculate formal charges

After making the P=S double bond, check formal charges again.

The structure should have a more reasonable formal-charge arrangement.

This is important because **Lewis structures are not chosen merely because every atom has an octet**.

You also want reasonable formal charges.

A simplified structure-selection priority is:

```text
1. Correct total number of electrons
2. Reasonable octets / electron configurations
3. Minimize unnecessary formal charges
4. Put negative formal charge on the more electronegative atom
5. Consider valid expanded-octet structures for elements
   that can accommodate them
```

This is a simplified high-school-level version and will need additional rules as the engine becomes more advanced.

---

# 13. Lewis structure pseudocode

```text
FUNCTION buildLewisStructure(formula):

    STEP 1:
        Parse formula into atoms and counts.

    STEP 2:
        Calculate total valence electrons.

    STEP 3:
        Apply ion charge adjustment if necessary.

        Ions carry a superscript charge in the formula:
            NH₄⁺ → charge +1
            OH⁻  → charge -1
            SO₄²⁻ → charge -2

        Adjust the total valence electron pool:
            negative charge → add electrons
            positive charge → remove electrons

        Example:
            OH⁻: O (6) + H (1) + 1 (ion charge) = 8 electrons

    STEP 4:
        Choose a candidate central atom.

        Usually:
            - H is never central
            - less electronegative atom is often central
            - consider special cases

    STEP 5:
        Connect surrounding atoms to the central atom
        with single bonds.

        NOTE: All bonds start as single bonds (order 1).
        This is an initial scaffold, not the final answer.
        Bond orders are refined in Steps 11–12 once formal
        charges reveal which bonds need to be upgraded.

    STEP 6:
        Subtract 2 electrons for every bond.

    STEP 7:
        Give outside atoms their required electrons.

        Most main-group atoms follow the octet rule:
        their outer shell (ns² np⁶) is full at 8 electrons.
        Hydrogen is the exception: it only wants 2 (1s²).

        For each outer atom:
            electrons needed = target - electrons already in bonds

        Example — Br in PBr₃S:
            target = 8
            already has 2 from its P-Br single bond
            needs 8 - 2 = 6 more → placed as 3 lone pairs

        Usually:
            8 electrons for main-group atoms (period 2+)
            2 electrons for H

    STEP 8:
        Put remaining electrons on the central atom.

        "Remaining" means the leftover pool after Step 7:
            total valence electrons
            - electrons used in bonds (Step 6)
            - electrons placed on outer atoms (Step 7)

        NOTE: This is purely Lewis-structure bookkeeping.
        The electronegativity rule (more electronegative
        atom gets all bonding electrons) is an
        oxidation-state rule only and does not affect which
        atom physically holds lone pairs in the structure.

    STEP 9:
        Check octets.

        An octet is 8 electrons around an atom — 4 pairs
        total counting both lone pairs and bond pairs.

        Count electrons around each atom:

            electrons around atom =
                lone pair electrons on that atom
              + 2 electrons per bond it participates in

        If a non-hydrogen atom has fewer than 8, the
        structure needs a multiple bond.
        The fix is Step 11: move a lone pair from an
        adjacent atom into the bond between them.

    STEP 10:
        Calculate formal charges.

        Formal charge splits bond electrons evenly between
        both atoms, unlike oxidation state which gives them
        all to the more electronegative atom.

        Formula:
            formal charge =
                valence electrons
              - nonbonding electrons
              - (bonding electrons / 2)

        Example — sulfur in an initial P-S single-bond
        structure (S has 3 lone pairs, 1 bond):

            S valence electrons    = 6
            S nonbonding electrons = 6
            S bonding electrons    = 2

            formal charge = 6 - 6 - (2/2) = -1

        Phosphorus in that same structure (4 single bonds,
        0 lone pairs):

            P valence electrons    = 5
            P nonbonding electrons = 0
            P bonding electrons    = 8

            formal charge = 5 - 0 - (8/2) = +1

        P at +1 and S at -1 is a signal the structure can
        be improved.

    STEP 11:
        If the structure has an unfavorable formal charge
        or an atom can improve its charge by forming a
        multiple bond:

            Move a lone pair from a neighboring atom
            into a bond.

        Formal charge classification:
            0            → ideal
            +1 or -1     → acceptable (if on the right atom)
            ±2 or larger → unfavorable
            positive on a highly electronegative atom → unfavorable
            negative on a low-electronegativity atom  → unfavorable

        Moving a lone pair into a bond concretely:
            Take 2 electrons sitting as a lone pair on the
            outer atom and convert them into a second bond
            between that atom and the central atom.

            Example:
                Before: P — S  (S lone pair, P=+1, S=-1)
                After:  P = S  (lone pair gone, double bond)

            After the double bond:
                S formal charge = 6 - 4 - (4/2) = 0
                P formal charge = 5 - 0 - (10/2) = 0

            Both atoms at 0 — the structure is now correct.

            Example:
                P-S
                 ↓
                P=S

    STEP 12:
        Recalculate formal charges.

    STEP 13:
        Generate alternative valid structures if necessary.

        Necessary when:
            - Two non-H atoms share the same electronegativity
              (same element, e.g. N₂O has two N atoms — both
              orderings N-N-O and N-O-N must be tried)
            - The chosen central atom produces formal charges
              that cannot be minimized even after exhausting
              all lone-pair moves (backtrack and retry with a
              different central atom)
            - The formula admits genuinely different skeletons
              (structural isomers), e.g. HCNO can be
              H-N=C=O or H-C≡N-O
            - Two different placements of a double bond give
              equally valid formal charges (resonance)
            - Period 3+ atoms (P, S, Cl) could use an
              expanded octet that produces better charges

        The primary path through Steps 4–12 uses the
        lowest-electronegativity rule to pick the central
        atom. Step 13 is a fallback: branch at Step 4 (try
        a different central atom) or at Step 11 (place the
        double bond on a different neighbor), run Steps 5–12
        for each branch, then compare results via Step 14.

        Note: H is never central regardless of
        electronegativity. Some elements (C, N) also have
        connectivity conventions that can override the
        electronegativity heuristic.

    STEP 14:
        Rank valid structures by this priority:

            1. Correct total electron count (hard requirement)
            2. All octets satisfied (non-H atoms have 8,
               H has 2)
            3. Minimize formal charges — prefer structures
               where more atoms are at 0
            4. Negative formal charge belongs on the more
               electronegative atom
            5. For period 3+ atoms, an expanded octet (10 or
               12 electrons) is allowed if it produces
               better formal charges

    STEP 15:
        Return the best candidate structure(s).
```

---

# 13a. Bond order

Bond order is not looked up from a table — it emerges from
the algorithm itself:

| Bond | Order | How it arises |
|---|---:|---|
| `A — B` | 1 | Starting scaffold (Step 5) |
| `A = B` | 2 | One lone pair moved into the bond (Step 11) |
| `A ≡ B` | 3 | Two lone pairs moved into the bond (Step 11 × 2) |

```text
bond order after algorithm =
    1 (baseline)
    + number of lone pairs moved into that bond
```

The bond order you end up with after the algorithm converges
is the real bond order for oxidation-state calculation.

---

# 14. Then oxidation-state calculation uses the Lewis structure

Once the Lewis structure exists, the oxidation-state engine becomes much easier.

```text
Lewis structure
       ↓
Identify every bond
       ↓
Get bond order
       ↓
Get electronegativities
       ↓
Assign bonding electrons to
more electronegative atom
       ↓
Keep lone-pair electrons with
their original atom
       ↓
Count assigned electrons
       ↓
OS = valence electrons - assigned electrons
```

For PBr₃S:

```text
          Br
          |
     Br — P = S
          |
          Br
```

Electronegativity:

```text
Br > S > P
```

Therefore:

```text
P-Br → Br gets electrons
P-Br → Br gets electrons
P-Br → Br gets electrons
P=S  → S gets electrons
```

Then:

```text
P  = +5
S  = -2
Br = -1
```

---

# 15. Current code: recommended conceptual changes

The current `Substance` class has:

```php
public ?float $charge = null;
```

Eventually, distinguish molecular charge from oxidation state.

Prefer something like:

```php
public ?int $oxidationState = null;
```

while `Reactant` keeps:

```php
public int $netCharge = 0;
```

These mean different things.

For example:

```text
H₂O

molecular net charge = 0

H oxidation state = +1
O oxidation state = -2
```

---

# 16. Existing oxidation-state list

The existing list:

```php
const oxidationStates = [
    'H'  => [-1, 1],
    'C'  => [-4, -3, -2, -1, 0, 1, 2, 3, 4],
    'N'  => [-3, -2, -1, 0, 1, 2, 3, 4, 5],
    'O'  => [-2, -1, 0, 1, 2],
    'F'  => [-1, 0],
    'P'  => [-3, -2, -1, 0, 1, 3, 5],
    'S'  => [-2, -1, 0, 1, 2, 3, 4, 5, 6],
    'Cl' => [-1, 0, 1, 3, 5, 7],
    'Se' => [-2, 0, 2, 4, 6],
    'Br' => [-1, 0, 1, 3, 5, 7],
    'I'  => [-1, 0, 1, 3, 5, 7],
    'At' => [-1, 0, 1, 3, 5, 7],
    'He' => [0],
    'Ne' => [0],
    'Ar' => [0],
    'Kr' => [0, 2],
    'Xe' => [0, 2, 4, 6, 8],
    'Rn' => [0, 2],
];
```

should be considered a **reference/validation dataset**.

Do not use:

```php
self::oxidationStates[$symbol][0]
```

as the calculation itself.

That simply chooses the first possible state.

The actual calculation should come from:

```text
structure
+
bond order
+
electronegativity
+
valence electrons
```

---

# 17. The overall target architecture

```text
Reactant
│
├── parseReactant()
│
├── substances
│
├── netCharge
│
└── calculateOxidationStates()
        │
        ├── buildLewisStructure()
        │       │
        │       ├── countValenceElectrons()
        │       ├── chooseCentralAtom()
        │       ├── createSingleBonds()
        │       ├── distributeElectrons()
        │       ├── calculateFormalCharges()
        │       ├── createMultipleBonds()
        │       └── validateStructure()
        │
        └── calculateOxidationStatesFromStructure()
                │
                ├── inspectBonds()
                ├── compareElectronegativities()
                ├── assignBondingElectrons()
                ├── countLonePairElectrons()
                ├── calculateOxidationState()
                └── validateNetCharge()
```

---

# 18. Suggested development order

Do **not** try to build the entire chemistry engine at once.

Build and test it in this order:

### Phase 1 — Electron counting

Get this working:

```text
H₂O → 8 total valence electrons
CO₂ → 16
NH₃ → 8
PCl₃ → 26
PBr₃S → 32
```

### Phase 2 — Simple Lewis structures

Start with:

```text
H₂
HCl
H₂O
NH₃
CH₄
CO₂
```

### Phase 3 — Formal charges

Add examples such as:

```text
NH₄⁺
OH⁻
H₃O⁺
```

### Phase 4 — Multiple bonds

Test:

```text
O₂
CO₂
N₂
```

### Phase 5 — Expanded octets

Then move to:

```text
PCl₅
SF₆
PBr₃S
```

### Phase 6 — Oxidation states

Once the Lewis structures are reliable, implement:

```text
Lewis structure
        ↓
electronegativity
        ↓
electron assignment
        ↓
oxidation states
```

This staged approach will make debugging **much** easier.

---

# Core principle

The engine should ultimately answer:

> **"What is the Lewis structure?"**

before asking:

> **"What are the oxidation states?"**

The oxidation-state calculation is relatively straightforward once the bonding structure is known.

The difficult chemistry problem is determining the structure from the formula.
