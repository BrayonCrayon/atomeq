<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $data = [
            ['symbol' => 'NH4', 'charge' => 1, 'name' => 'Ammonium'],
            ['symbol' => 'H3O', 'charge' => 1, 'name' => 'Hydronium'],
            ['symbol' => 'OH', 'charge' => -1, 'name' => 'Hydroxide'],
            ['symbol' => 'NO3', 'charge' => -1, 'name' => 'Nitrate'],
            ['symbol' => 'NO2', 'charge' => -1, 'name' => 'Nitrite'],
            ['symbol' => 'CN', 'charge' => -1, 'name' => 'Cyanide'],
            ['symbol' => 'SCN', 'charge' => -1, 'name' => 'Thiocyanate'],
            ['symbol' => 'CH3COO', 'charge' => -1, 'name' => 'Acetate'],
            ['symbol' => 'C2H3O2', 'charge' => -1, 'name' => 'Acetate - Alt Formula'],
            ['symbol' => 'HCO3', 'charge' => -1, 'name' => 'Bicarbonate / Hydrogen Carbonate'],
            ['symbol' => 'HSO4', 'charge' => -1, 'name' => 'Bisulfate / Hydrogen Sulfate'],
            ['symbol' => 'HSO3', 'charge' => -1, 'name' => 'Bisulfite / Hydrogen Sulfite'],
            ['symbol' => 'H2PO4', 'charge' => -1, 'name' => 'Dihydrogen Phosphate'],
            ['symbol' => 'MnO4', 'charge' => -1, 'name' => 'Permanganate'],
            ['symbol' => 'ClO', 'charge' => -1, 'name' => 'Hypochlorite'],
            ['symbol' => 'ClO2', 'charge' => -1, 'name' => 'Chlorite'],
            ['symbol' => 'ClO3', 'charge' => -1, 'name' => 'Chlorate'],
            ['symbol' => 'ClO4', 'charge' => -1, 'name' => 'Perchlorate'],
            ['symbol' => 'BrO', 'charge' => -1, 'name' => 'Hypobromite'],
            ['symbol' => 'BrO2', 'charge' => -1, 'name' => 'Bromite'],
            ['symbol' => 'BrO3', 'charge' => -1, 'name' => 'Bromate'],
            ['symbol' => 'BrO4', 'charge' => -1, 'name' => 'Perbromate'],
            ['symbol' => 'IO', 'charge' => -1, 'name' => 'Hypoiodite'],
            ['symbol' => 'IO2', 'charge' => -1, 'name' => 'Iodite'],
            ['symbol' => 'IO3', 'charge' => -1, 'name' => 'Iodate'],
            ['symbol' => 'IO4', 'charge' => -1, 'name' => 'Periodate'],
            ['symbol' => 'SO4', 'charge' => -2, 'name' => 'Sulfate'],
            ['symbol' => 'SO3', 'charge' => -2, 'name' => 'Sulfite'],
            ['symbol' => 'CO3', 'charge' => -2, 'name' => 'Carbonate'],
            ['symbol' => 'CrO4', 'charge' => -2, 'name' => 'Chromate'],
            ['symbol' => 'Cr2O7', 'charge' => -2, 'name' => 'Dichromate'],
            ['symbol' => 'C2O4', 'charge' => -2, 'name' => 'Oxalate'],
            ['symbol' => 'S2O3', 'charge' => -2, 'name' => 'Thiosulfate'],
            ['symbol' => 'HPO4', 'charge' => -2, 'name' => 'Hydrogen Phosphate'],
            ['symbol' => 'O2', 'charge' => -2, 'name' => 'Peroxide'],
            ['symbol' => 'SiO3', 'charge' => -2, 'name' => 'Silicate'],
            ['symbol' => 'PO4', 'charge' => -3, 'name' => 'Phosphate'],
            ['symbol' => 'PO3', 'charge' => -3, 'name' => 'Phosphite'],
            ['symbol' => 'AsO4', 'charge' => -3, 'name' => 'Arsenate'],
            ['symbol' => 'BO3', 'charge' => -3, 'name' => 'Borate'],
        ];
        DB::table('polyatomic_ions')->insert($data);
    }
};
