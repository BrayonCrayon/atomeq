<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elements', function (Blueprint $table) {
            $table->unsignedSmallInteger('valency')->nullable()->after('valence');
        });
    }

    public function down(): void
    {
        Schema::table('elements', function (Blueprint $table) {
            $table->dropColumn('valency');
        });
    }
};
