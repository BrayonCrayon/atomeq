<?php

use App\Models\PolyatomicIon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        PolyatomicIon::create(['name' => 'Nitrosonium', 'symbol' => 'NO', 'charge' => 1, 'is_cation' => true]);
        PolyatomicIon::create(['name' => 'Nitronium', 'symbol' => 'NO2', 'charge' => 1, 'is_cation' => true]);
        PolyatomicIon::create(['name' => 'Mercury(I)', 'symbol' => 'Hg2', 'charge' => 2, 'is_cation' => true]);

        DB::table('polyatomic_ions')
            ->whereIn('symbol', ['H3O', 'NH4'])
            ->update(['is_cation' => true]);
    }
};
