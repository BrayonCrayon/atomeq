<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    private const elements = [
        'Nihonium' => 2003,
        'Flerovium' => 1998,
        'Moscovium' => 2003,
        'Livermorium' => 2000,
        'Tennessine' => 2010,
        'Oganesson' => 2002,
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $elementsMissingYears = DB::table('element_discoveries as ED')
                ->select(['E.name as element_name', 'ED.id as element_discovery_id'])
                ->join('elements as E', 'E.id', '=', 'ED.element_id')
                ->join('discoverers as D', 'D.id', '=', 'ED.discoverer_id')
                ->whereNull('ED.year')
                ->whereNotIn('D.name', ['Prehistoric', 'Early historic times'])
                ->get();

            $elementsMissingYears->each(function ($element) {
                DB::table('element_discoveries as ED')
                    ->where('ED.id', '=', $element->element_discovery_id)
                    ->update(['year' => self::elements[$element->element_name]]);
            });
        });
    }
};
