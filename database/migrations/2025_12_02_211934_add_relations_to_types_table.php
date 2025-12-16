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

            $parentsNames = ['metal', 'nonmetal', 'metalloid'];
            $nonMetalChildrenNames = ['noble-gas', 'halogen'];

            $types = DB::table('types')->get();
            [$metal, $_, $nonmetal] = $types->whereIn('name', $parentsNames)->sortBy('name')->values()->toArray();
            $children = $types->whereNotIn('name', $parentsNames);
            $nonMetalKids = $types->whereIn('name', $nonMetalChildrenNames);
            $metalKids = $children->whereNotIn('name', array_merge($parentsNames, $nonMetalChildrenNames));

            $nonMetalKids = $nonMetalKids->map(function ($nonMetal) use ($nonmetal) {
                $nonMetal->parent_id = $nonmetal->id;
                return (array)$nonMetal;
            });

            $metalKids = $metalKids->map(function ($metalKid) use ($metal) {
                $metalKid->parent_id = $metal->id;
                return (array)$metalKid;
            });

            DB::table('types')->upsert($nonMetalKids->toArray(), ['id'], ['parent_id']);
            DB::table('types')->upsert($metalKids->toArray(), ['id'], ['parent_id']);
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
