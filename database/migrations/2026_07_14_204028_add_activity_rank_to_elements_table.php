<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    const metalsLeastToMost = ["Pt", "Au", "Ag", "Cu", "Pb", "Sn", "Fe", "Zn", "Al", "Mg", "Ca", "Li", "Na", "K"];
    const nonMetalsLeastToMost = ["P", "S", "I", "Br", "Cl", "O", "F"];

    public function up(): void
    {

        for ($i = 1; $i <= count(self::metalsLeastToMost); $i++) {
            $metal = self::metalsLeastToMost[$i - 1];

            DB::table('elements')
                ->where('symbol', $metal)
                ->update(['activity_rank' => $i]);
        }

        for ($i = 1; $i <= count(self::nonMetalsLeastToMost); $i++) {
            $nonMetal = self::nonMetalsLeastToMost[$i - 1];

            DB::table('elements')
                ->where('symbol', $nonMetal)
                ->update(['activity_rank' => $i]);
        }
    }

    public function down(): void
    {
        DB::table('elements')
            ->where('symbol', [...self::metalsLeastToMost, ...self::nonMetalsLeastToMost])
            ->update(['activity_rank' => null]);
    }
};
