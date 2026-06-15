<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    const GROUP_MATCHINGS = [
        1 => 1,
        2 => 2,
        13 => 3,
        14 => 4,
        15 => 3,
        16 => 2,
        17 => 1,
        18 => 0,
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
