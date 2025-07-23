<?php

use App\Models\Discoverer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // TODO: link the discoverers to elements through discovery table in the job?
    // query the discoverer table, get the names and map them to elements that miss discoverers?
    // map:
        // Darmstadtium, Roentgenium, Copernicium = GSI Helmholtz Centre for Heavy Ion Research
        // Nihonium = RIKEN
        // Flerovium, Moscovium, Livermorium, Oganesson = JINR and LLNL
        // Tennessine = Oak Ridge National Laboratory and Vanderbilt University and
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
        // create an array of all the discoverers that need to get into the database
        DB::beginTransaction();
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

        // Load all elements
        $elements = DB::table('elements')->select('id', 'name')->get();

        // For each entry in $discoverersToAdd map a data structure that will be inserted into the pivot table
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
        });
        DB::rollBack();
        dd($dataToInsert->flatten()->toArray()); // TODO: too much flatenning
    }
};
