<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    const targets = [
        [
            'name' => 'Geselleschaft für Schwerionenforschung (GSI)',
            'id' => null,
            'elements' => ['Darmstadtium', 'Roentgenium', 'Copernicium']
        ],
        [
            'name' => 'RIKEN',
            'id' => null,
            'elements' => ['Nihonium']
        ],
        [
            'name' => 'Joint Institute for Nuclear Research',
            'id' => null,
            'elements' => ['Flerovium', 'Moscovium', 'Livermorium', 'Oganesson']
        ],
        [
            'name' => 'Lawrence Livermore National Laboratory',
            'id' => null,
            'elements' => ['Flerovium', 'Moscovium', 'Livermorium', 'Oganesson']
        ],
        [
            'name' => 'Oak Ridge National Laboratory',
            'id' => null,
            'elements' => ['Tennessine']
        ],
        [
            'name' => 'Vanderbilt University',
            'id' => null,
            'elements' => ['Tennessine']
        ],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $discoverersToAdd = collect(self::targets)->map(function (array $discoverer) {
                $discovererFound = DB::table('discoverers')->where('name', $discoverer['name'])->first();

                if ($discovererFound) {
                    $discoverer['id'] = $discovererFound->id;
                } else {
                    $id = DB::table('discoverers')->insertGetId([
                        'name' => $discoverer['name'],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                    $discoverer['id'] = $id;
                }

                return $discoverer;
            });

            $elements = DB::table('elements')->select('id', 'name')->get();

            $dataToInsert = $discoverersToAdd->map(function (array $item) use ($elements) {
                $relatedElementIds = $elements->whereIn('name', $item['elements'])->pluck('id');

                return $relatedElementIds->map(function (int $elementId) use ($item) {
                    return [
                        'element_id' => $elementId,
                        'discoverer_id' => $item['id'],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ];
                });
            })->flatten(1)->toArray();
            DB::table('element_discoveries')->insert($dataToInsert);
        });
    }
};
