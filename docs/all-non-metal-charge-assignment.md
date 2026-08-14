# Charge Assignment for All-Non-Metal Compounds

## The Problem

The current `assignCharges()` pairwise left-to-right electronegativity comparison breaks down
when a compound contains only non-metals and one element sits between two others of differing
electronegativity.

### Example: HCCl3 (chloroform)

The algorithm processes adjacent pairs:

| Pair | Comparison | Result |
|------|-----------|--------|
| H vs C | H (2.2) < C (2.55) → C is more electronegative | H = +1, C = -4 |
| C vs Cl | C (2.55) < Cl (3.16) → Cl is more electronegative | C stays -4, Cl = -1 |
| Cl (last) | no next element | netCharge += -1 × 3 |

This yields `netCharge = 1 + (-4) + (-3) = -6`, which is wrong. The test expectation of
`C = -4` and `netCharge = 0` is mathematically contradictory.

## Correct Oxidation State: C = +2

The correct oxidation state of C in HCCl3 is **+2**, solved via the charge balance equation:

```
(H charge × H atoms) + (C charge × C atoms) + (Cl charge × Cl atoms) = 0
(+1)(1)  +  C  +  (-1)(3) = 0
C = +2
```

| Element | Rule | Charge | Atoms | Contribution |
|---------|------|--------|-------|--------------|
| H | +1 when bonded to non-metals | +1 | 1 | +1 |
| Cl | Halogen → -1 | -1 | 3 | -3 |
| C | Solved by balance | **+2** | 1 | +2 |
| | | | **Net** | **0** ✓ |

## Rules for All-Non-Metal Compounds

Apply these priority rules in order; remaining elements solve the balance equation:

1. **H = +1** when bonded to non-metals (exception: metal hydrides where H = -1)
2. **Halogens (F, Cl, Br, I) = -1** (most electronegative group)
3. **O = -2** (unless bonded only to F, or in a peroxide where O = -1)
4. **Remaining elements** — solve algebraically using the net charge balance:
   ```
   sum(charge × atom_count) = compound net charge (usually 0)
   ```

## Why the Pairwise Algorithm Fails Here

Carbon in HCCl3 is more electronegative than H but less electronegative than Cl. The
pairwise algorithm assigns C a negative charge from the H-C comparison, then never
corrects it during the C-Cl comparison (because C is already set and only its sign is
checked, not re-evaluated relative to all neighbors).

The fix requires recognising "middle" elements in all-non-metal chains and determining
their charge by balance rather than by a single pairwise electronegativity flip.

## Fix Required

- Update `ReactantTest.php` line 122: change `C charge` expectation from `-4` to `+2`
- Update `assignCharges()` to apply the priority rules above before falling back to the
  pairwise comparison, specifically handling the case where all substances are non-metals
