<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('types', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->default(null)->references('id')->on('types');
        });
    }

    public function down(): void
    {
        Schema::table('types', function (Blueprint $table) {
            $table->dropForeign('types_parent_id_foreign');
            $table->dropColumn('parent_id');
        });
    }
};
