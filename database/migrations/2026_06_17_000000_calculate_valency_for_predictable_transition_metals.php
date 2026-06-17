<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    const GROUP_MATCHINGS = [
        3  => 3, // Sc, Y, La — always +3
        12 => 2, // Zn, Cd, Hg — always +2
    ];

    public function up(): void
    {
        foreach (self::GROUP_MATCHINGS as $group => $valency) {
            DB::table('elements')
                ->where('elements.group', $group)
                ->update(['elements.valency' => $valency]);
        }
    }
};
