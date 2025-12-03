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

            $nonMetals->each(function ($nonmetal) use ($parents) {
                $nonmetalParent = $parents->where('name', '=', 'nonmetal');
                $nonmetal->parent_id = $nonmetalParent[0]->id;
            });

            // TODO: this is indexed weirdly
            // TODO: fix indexing
            $metals->each(function ($metal) use ($parents) {
                $metalParent = $parents->where('name', '=', 'metal');
                dd($metalParent[6]);
                $metal->parent_id = $metalParent->id;
            });

            // TODO: commit updated types to the database
        });
    }

    public function down(): void
    {
        Schema::table('types', function (Blueprint $table) {
//            $table->dropForeign('parent_id_types_index'); // TODO: Double check the naming of this when the column is created.
        });
    }
};
