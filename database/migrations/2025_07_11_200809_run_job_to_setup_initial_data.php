<?php

use App\Jobs\ImportElementDataJob;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        ImportElementDataJob::dispatch()->withoutDelay();
    }

    public function down(): void
    {
        DB::table('element_discoveries')->delete();
        DB::table('elements')->delete();
        DB::table('types')->delete();
        DB::table('element_states')->delete();
        DB::table('element_discoveries')->delete();
    }
};
