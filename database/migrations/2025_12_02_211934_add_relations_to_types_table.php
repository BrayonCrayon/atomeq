<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        try {
            DB::transaction(function () {
                Schema::table('types', function (Blueprint $table) {
                    $table->foreignId('parent_id')->nullable()->default(null)->references('id')->on('types');
                });

                $types = DB::table('types')->get();
                $parents = $types->whereIn('name', ['metal', 'nonmetal', 'metalloid']);
                $children = $types->whereNotIn('name', $parents);
                $nonMetals = $types->whereIn('name', ['noble-gas', 'halogen']);
                $metals = $children->whereNotIn('name', ['metal', 'nonmetal', 'metalloid', 'noble-gas', 'halogen']);

                $nonMetals = $nonMetals->map(function ($nonMetal) use ($parents) {
                    $nonMetalParent = $parents->where('name', '=', 'nonmetal')->values();
                    $nonMetal->parent_id = $nonMetalParent[0]->id;

                    return (array)$nonMetal;
                });

                $metals = $metals->map(function ($metal) use ($parents) {
                    $metalParent = $parents->where('name', '=', 'metal')->values();
                    $metal->parent_id = $metalParent[0]->id;

                    return (array)$metal;
                });

                DB::table('types')->upsert($nonMetals->toArray(), ['id'], ['parent_id']);
                DB::table('types')->upsert($metals->toArray(), ['id'], ['parent_id']);

            });
        } catch (Exception $exception) {
            throw new Error("Force rollback with $exception");

        }
    }

    public function down(): void
    {
        Schema::table('types', function (Blueprint $table) {
            $table->dropForeign('types_parent_id_foreign');
            $table->dropColumn('parent_id');
        });
    }
};
