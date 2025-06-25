<?php

namespace Tests\Jobs;

use App\Jobs\ImportElementDataJob;
use Illuminate\Support\Facades\Storage;

test('will insert element types into the database', function () {
    $csvContent = Storage::disk()->get("Periodic_Table_of_Elements.csv");
    $lines = explode(PHP_EOL, $csvContent);
    $headers = collect(str_getcsv(array_shift($lines)));

    $rows = collect($lines);
    $data = $rows->map(fn($row) => str_getcsv($row));

    (new ImportElementDataJob())->handle();

    $data->each(function($row) {
       $this->assertDatabaseHas('types', ['name' => $row[15]]);
    });
});
