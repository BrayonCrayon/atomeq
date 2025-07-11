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
        // Tennessine = Oak Ridge National Laboratory and Vanderbilt University and JINR

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('discoverers')
            ->insert([
                ['name' => 'GSI Helmholtz Centre for Heavy Ion Research'],
                ['name' => 'RIKEN'],
                ['name' => 'JINR and LLNL'],
                ['name' => 'Oak Ridge National Laboratory and Vanderbilt University and JINR'],
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
