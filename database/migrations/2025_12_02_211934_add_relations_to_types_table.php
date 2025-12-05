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
            $parents = $types->whereIn('name', ['metal', 'nonmetal', 'metalloid']);
            $children = $types->whereNotIn('name', $parents);
            $nonMetals = $types->whereIn('name', ['noble-gas', 'halogen']);
            $metals = $children->whereNotIn('name', $nonMetals);

//            TODO: standardize this: either each or map
            $nonMetals = $nonMetals->each(function ($nonmetal) use ($parents) {
                $nonmetalParent = $parents->where('name', '=', 'nonmetal');
                $nonmetal->parent_id = $nonmetalParent[0]->id;
            })->map(fn($item) => (array)$item);

            $metals = $metals->map(function ($metal) use ($parents) {
                $metalParent = $parents->where('name', '=', 'metal')->values();
                $metal->parent_id = $metalParent[0]->id;

                return (array) $metal;
            });


//            TODO: Fix this
//            TODO: potentially metal is not filtered out properly, and is updating everything to parent id 7
//            DB::table('types')->whereIn('id', $nonMetals->pluck("id") )->update([
//                "parent_id" => $nonMetals[0]->parent_id
//            ]);
//            dd($nonMetals->toArray());
            DB::table('types')->upsert($nonMetals->toArray(), ['id'], ['parent_id']);
            DB::table('types')->upsert($metals->toArray(), ['id', 'parent_id'], ['parent_id']);
            //throw new Error("Force rollback");
        });
    }

    public function down(): void
    {
        Schema::table('types', function (Blueprint $table) {
            $table->dropColumn('parent_id');
            //$table->dropForeign('types_parent_id_foreign');
        });
    }
};
