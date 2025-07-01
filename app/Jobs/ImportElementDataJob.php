<?php

namespace App\Jobs;

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

        $data = $lines->map(fn($row) => str_getcsv($row));

        $data->map(fn($line) => $line[15])
            ->unique()
            ->each(function ($type) {
                Type::create(['name' => $type]);
            });

        $data->map(fn($line) => $line[9])
            ->filter()
            ->unique()
            ->each(function ($state) {
                ElementState::create(['name' => $state]);
            });
    }
}
