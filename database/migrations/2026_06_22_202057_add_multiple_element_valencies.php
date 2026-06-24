<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    const ELEMENT_VALENCIES = [
        // d-block Groups 4–11, Period 4
        22 => [2, 3, 4],         // Titanium
        23 => [2, 3, 4, 5],      // Vanadium
        24 => [2, 3, 6],         // Chromium
        25 => [2, 3, 4, 6, 7],   // Manganese
        26 => [2, 3],             // Iron
        27 => [2, 3],             // Cobalt
        28 => [2, 3],             // Nickel
        29 => [1, 2],             // Copper

        // d-block Groups 4–11, Period 5
        40 => [4],                // Zirconium
        41 => [3, 5],             // Niobium
        42 => [2, 3, 4, 5, 6],   // Molybdenum
        43 => [4, 7],             // Technetium
        44 => [2, 3, 4, 6, 8],   // Ruthenium
        45 => [3],                // Rhodium
        46 => [2, 4],             // Palladium
        47 => [1],                // Silver

        // Lanthanides (Ce–Lu, no group in DB)
        58 => [3, 4],             // Cerium
        59 => [3, 4],             // Praseodymium
        60 => [3],                // Neodymium
        61 => [3],                // Promethium
        62 => [2, 3],             // Samarium
        63 => [2, 3],             // Europium
        64 => [3],                // Gadolinium
        65 => [3, 4],             // Terbium
        66 => [3],                // Dysprosium
        67 => [3],                // Holmium
        68 => [3],                // Erbium
        69 => [2, 3],             // Thulium
        70 => [2, 3],             // Ytterbium
        71 => [3],                // Lutetium

        // d-block Groups 4–11, Period 6
        72 => [4],                // Hafnium
        73 => [5],                // Tantalum
        74 => [4, 6],             // Wolfram
        75 => [4, 7],             // Rhenium
        76 => [4, 8],             // Osmium
        77 => [3, 4],             // Iridium
        78 => [2, 4],             // Platinum
        79 => [1, 3],             // Gold

        // Actinides (Th–Lr, no group in DB)
        90 => [4],                // Thorium
        91 => [4, 5],             // Protactinium
        92 => [3, 4, 5, 6],      // Uranium
        93 => [4, 5, 6, 7],      // Neptunium
        94 => [3, 4, 5, 6],      // Plutonium
        95 => [3, 4, 5, 6],      // Americium
        96 => [3, 4],             // Curium
        97 => [3, 4],             // Berkelium
        98 => [2, 3],             // Californium
        99 => [2, 3],             // Einsteinium
        100 => [2, 3],            // Fermium
        101 => [2, 3],            // Mendelevium
        102 => [2, 3],            // Nobelium
        103 => [3],               // Lawrencium

        // Transactinides Groups 4–11 (predicted by group analogy)
        104 => [4],               // Rutherfordium
        105 => [5],               // Dubnium
        106 => [6],               // Seaborgium
        107 => [7],               // Bohrium
        108 => [8],               // Hassium
        109 => [3, 6],            // Meitnerium
        110 => [6, 8],            // Darmstadtium
        111 => [3, 5],            // Roentgenium
    ];

    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $atomNumbers = array_keys(self::ELEMENT_VALENCIES);

        $elementsToInsert = DB::table('elements')
            ->select(['id', 'atomic_number'])
            ->whereIn('atomic_number', $atomNumbers)
            ->get()
            ->mapWithKeys(fn ($element) => [$element->atomic_number => $element->id]);

        $insertArray = [];

        foreach (self::ELEMENT_VALENCIES as $atomicNumber => $valencies) {

            foreach ($valencies as $valency) {
                $insertArray[] = [
                    'element_id' => $elementsToInsert[$atomicNumber],
                    'valency' => $valency,
                ];
            }
        }

        DB::table('element_valencies')->insert($insertArray);
    }

    public function down(): void
    {
        $elementIds = DB::table('elements')
            ->whereIn('atomic_number', array_keys(self::ELEMENT_VALENCIES))
            ->pluck('id');

        DB::table('element_valencies')
            ->whereIn('element_id', $elementIds)
            ->delete();
    }
};
