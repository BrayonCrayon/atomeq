<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public const targets = [
        [
            'name' => 'Geselleschaft für Schwerionenforschung (GSI)',
            'id' => null,
            'elements' => ['Darmstadtium', 'Roentgenium', 'Copernicium'],
        ],
        [
            'name' => 'RIKEN',
            'id' => null,
            'elements' => ['Nihonium'],
        ],
        [
            'name' => 'Joint Institute for Nuclear Research',
            'id' => null,
            'elements' => ['Flerovium', 'Moscovium', 'Livermorium', 'Oganesson'],
        ],
        [
            'name' => 'Lawrence Livermore National Laboratory',
            'id' => null,
            'elements' => ['Flerovium', 'Moscovium', 'Livermorium', 'Oganesson'],
        ],
        [
            'name' => 'Oak Ridge National Laboratory',
            'id' => null,
            'elements' => ['Tennessine'],
        ],
        [
            'name' => 'Vanderbilt University',
            'id' => null,
            'elements' => ['Tennessine'],
        ],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $knownDiscoverers = DB::table('discoverers')
                ->whereIn('name', collect(self::targets)->pluck('name'))
                ->get();

            $discoverersToAdd = collect(self::targets)->map(function (array $discoverer) use ($knownDiscoverers) {
                $discovererFound = $knownDiscoverers->where('name', $discoverer['name'])->first();

                $discoverer['id'] = $discovererFound->id ??
                    DB::table('discoverers')->insertGetId(['name' => $discoverer['name'], ...$this->getTimeStamps()]);

                return $discoverer;
            });

            $elements = DB::table('elements')->select('id', 'name')->get();

            $dataToInsert = $discoverersToAdd->map(function (array $item) use ($elements) {
                $relatedElementIds = $elements->whereIn('name', $item['elements'])->pluck('id');

                return $this->createElementDiscoveriesStruct($relatedElementIds, $item['id']);
            })->flatten(1)->toArray();
            DB::table('element_discoveries')->insert($dataToInsert);
        });
    }

    private function getTimeStamps(): array
    {
        return [
            'updated_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ];
    }

    private function createElementDiscoveriesStruct(Collection $elementIds, int $discovererId): Collection
    {
        return $elementIds->map(fn (int $elementId) => [
            'element_id' => $elementId,
            'discoverer_id' => $discovererId,
            ...$this->getTimeStamps(),
        ]);
    }
};
