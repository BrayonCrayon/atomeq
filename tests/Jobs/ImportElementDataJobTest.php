<?php

namespace Tests\Jobs;

use App\Jobs\ImportElementDataJob;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $csvContent = Storage::disk()->get('Periodic_Table_of_Elements.csv');
    $lines = collect(explode(PHP_EOL, $csvContent));
    $lines->shift();
    $lines->pop();
    $this->csvData = $lines->map(fn ($row) => str_getcsv($row));
});

test('will insert element types into the database', function () {
    (new ImportElementDataJob)->handle();

    $this->csvData->filter(fn ($row) => isset($row[15]))->each(function ($row) {
        $this->assertDatabaseHas('types', ['name' => $row[15]]);
    });
});

test('will insert element states into the database', function () {
    $elementStates = $this->csvData->map(function ($row) {
        return $row[9];
    })->filter()->unique();

    (new ImportElementDataJob)->handle();

    $elementStates->each(function (string $state) {
        $this->assertDatabaseHas('element_states', [
            'name' => $state,
        ]);
    });
});

test('will insert discoverers into the database', function () {
    $discoverers = $this->csvData->map(function ($row) {
        return $row[23];
    })->filter()->unique();

    (new ImportElementDataJob)->handle();

    $discoverers->each(function (string $discoverer) {
        $this->assertDatabaseHas('discoverers', [
            'name' => $discoverer,
        ]);
    });
});

test('will insert elements into the database', function () {
    $elements = $this->csvData->map(function ($row) {
        return [
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
        ];
    });

    (new ImportElementDataJob)->handle();

    $elements->each(function ($element) {
        $this->assertDatabaseHas('elements', $element);
    });
});
test('will insert element discoveries into the database');
