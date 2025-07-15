<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // TODO: link the discoverers to elements through discovery table in the job?
    // query the discoverer table, get the names and map them to elements that miss discoverers?
    // map:
        // Darmstadtium, Roentgenium, Copernicium = GSI Helmholtz Centre for Heavy Ion Research
        // Nihonium = RIKEN
        // Flerovium, Moscovium, Livermorium, Oganesson = JINR and LLNL
        // Tennessine = Oak Ridge National Laboratory and Vanderbilt University and

    // TODO:
    // create a DiscovererType table
        // a person, institute
        // add a column to Discoverer's table for type

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('discoverers')
            ->insert([
                ['name' => 'GSI Helmholtz Centre for Heavy Ion Research', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'RIKEN', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'JINR', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'LLNL', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Oak Ridge National Laboratory', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Vanderbilt University', 'created_at' => now(), 'updated_at' => now()],
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('discoverers')->whereIn('name', [
            'GSI Helmholtz Centre for Heavy Ion Research',
            'RIKEN',
            'JINR and LLNL',
            'Oak Ridge National Laboratory and Vanderbilt University and JINR',
        ])->delete();
    }
};
