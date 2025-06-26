<?php

namespace App\Jobs;

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

        $lines = explode(PHP_EOL, $csvContents);
        array_shift($lines);

        $data = collect($lines)->map(fn($row) => str_getcsv($row));

        $data->map(function ($line) {
            if (!isset($line[15])) {
                return;
            }

            return $line[15] ?? '';
        })->unique()->each(function ($type) {
            Type::create(['name' => $type]);
        });

    }
}
