<?php

namespace App\Jobs;

use App\Models\Discoverer;
use App\Models\Element;
use App\Models\ElementState;
use App\Models\Type;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ImportElementDataJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $csvContents = Storage::disk('local')->get('Periodic_Table_of_Elements.csv');

        $lines = collect(explode(PHP_EOL, $csvContents));
        $lines->shift();
        $lines->pop();

        $data = $lines->map(fn ($row) => str_getcsv($row));

        $data->map(fn ($line) => $line[15])
            ->unique()
            ->each(function ($type) {
                Type::create(['name' => $type]);
            });

        $data->map(fn ($line) => $line[9])
            ->filter()
            ->unique()
            ->each(function ($state) {
                ElementState::create(['name' => $state]);
            });

        $data->map(fn ($line) => $line[23])
            ->filter()
            ->unique()
            ->each(function ($discoverer) {
                Discoverer::create(['name' => $discoverer]);
            });

        $data->map(function ($row) {
            Element::create([
                'name' => $row[1],
                'atomic_number' => (int) $row[0],
                'atomic_mass' => (float) $row[3],
                'symbol' => $row[2],
                'neutrons' => (int) $row[4],
                'protons' => (int) $row[5],
                'electrons' => (int) $row[6],
                'period' => (int) $row[7],
                'group' => (int) $row[8],
                //           'element_state_id' => ,
                'radioactive' => $row[10] === 'yes',
                'natural' => $row[11] === 'yes',
                'metal' => $row[12] === 'yes',
                'metalloid' => $row[14] === 'yes',
                // 'type_id' => $row[],
                'atomic_radius' => (float) $row[16],
                'electronegativity' => (float) $row[17],
                'first_ionization' => (float) $row[18],
                'density' => $row[19],
                'melting_point' => (float) $row[20],
                'boiling_point' => (float) $row[21],
                'isotopes' => (int) $row[22],
                'specific_heat' => (int) $row[25],
                'shells' => (int) $row[26],
                'valence' => (int) $row[27],
            ]);
        });
    }
}
