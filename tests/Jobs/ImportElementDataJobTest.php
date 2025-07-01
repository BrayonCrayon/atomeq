<?php

namespace Tests\Jobs;

use App\Jobs\ImportElementDataJob;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $csvContent = Storage::disk()->get('Periodic_Table_of_Elements.csv');
    $lines = collect(explode(PHP_EOL, $csvContent));
    $lines->shift();
    $lines->pop();
    $this->csvData = $lines->map(fn($row) => str_getcsv($row));
});

test('will insert element types into the database', function () {
    (new ImportElementDataJob())->handle();

    $this->csvData->filter(fn($row) => isset($row[15]))->each(function($row) {
       $this->assertDatabaseHas('types', ['name' => $row[15]]);
    });
});

test('will insert element states into the database', function () {
    $elementStates = $this->csvData->map(function ($row) {
        return $row[9];
    })->filter()->unique();

    (new ImportElementDataJob())->handle();

    $elementStates->each(function (string $state) {
        $this->assertDatabaseHas('element_states', [
            'name' => $state
        ]);
    });
});
