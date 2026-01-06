<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        DB::transaction(function () {
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
};
