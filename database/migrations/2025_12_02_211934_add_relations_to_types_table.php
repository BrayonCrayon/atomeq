<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            Schema::table('types', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->default(null)->references('id')->on('types');
            });

            $types = DB::table('types')->get();

            $children = $types->filter(function ($type) {
                return $type['name'] !== 'metal' || $type['name'] !== 'nonmetal' || $type['name'] !== 'metalloid';
            });

            // TODO: we stopped here
            // TODO: highlight the nonmetal as a subgroup only as part of the overall nonmetal parent group on parent select
            $nonMetals = collect(['noble-gas', 'halogen']);
            $metalChildren = $children->filter(function ($child) {
                // return all types that are metal types
            });
        });
    }

    public function down(): void
    {
        Schema::table('types', function (Blueprint $table) {
            $table->dropForeign('parent_id_types_index'); // TODO: Double check the naming of this when the column is created.
        });
    }
};
