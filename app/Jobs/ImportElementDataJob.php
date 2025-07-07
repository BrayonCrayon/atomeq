<?php

namespace App\Jobs;

use App\Models\Discoverer;
use App\Models\Element;
use App\Models\ElementDiscovery;
use App\Models\ElementState;
use App\Models\Type;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportElementDataJob implements ShouldQueue
{
    use Queueable;
    public function handle(): void
    {
        // TODO mass insertions

        $csvContents = Storage::disk('local')->get('Periodic_Table_of_Elements.csv');

        $lines = collect(explode(PHP_EOL, $csvContents));
        $headers = collect(explode(',', $lines->shift()));
        $lines->pop();
        $data = $lines->map(function ($row) use ($headers) {
            return $headers->combine(str_getcsv($row))->toArray();
        });

        $types = $data->map(fn ($line) => ['name' => $line['Type']])
            ->unique()->toArray();
        Type::insert($types);

        $phases = $data->map(fn ($line) => ['name' => $line['Phase']])
            ->filter()
            ->unique()->toArray();
        ElementState::insert($phases);

        $data->map(fn ($line) => $line['Discoverer'])
            ->filter()
            ->unique()
            ->each(function ($discoverer) {
                Discoverer::create(['name' => $discoverer]);
            });

        $elementStates = ElementState::all();
        $elementTypes = Type::all();
        $discoverers = Discoverer::query()->get();

        $data->each(function ($row) use ($elementStates, $elementTypes, $discoverers) {
            $rowState = $elementStates->first(fn ($item) => $item->name === $row['Phase']);
            $rowType = $elementTypes->first(fn ($item) => $item->name === $row['Type']);

            $element = Element::create([
                'name' => $row['Element'],
                'atomic_number' => (int) $row['AtomicNumber'],
                'atomic_mass' => (float) $row['AtomicMass'],
                'symbol' => $row['Symbol'],
                'neutrons' => (int) $row['NumberofNeutrons'],
                'protons' => (int) $row['NumberofProtons'],
                'electrons' => (int) $row['NumberofElectrons'],
                'period' => (int) $row['Period'],
                'group' => (int) $row['Group'],
                'element_state_id' => $rowState->id,
                'radioactive' => $row['Radioactive'] === 'yes',
                'natural' => $row['Natural'] === 'yes',
                'metal' => $row['Metal'] === 'yes',
                'metalloid' => $row['Metalloid'] === 'yes',
                'type_id' => $rowType->id,
                'atomic_radius' => (float) $row['AtomicRadius'],
                'electronegativity' => (float) $row['Electronegativity'],
                'first_ionization' => (float) $row['FirstIonization'],
                'density' => $row['Density'],
                'melting_point' => (float) $row['MeltingPoint'],
                'boiling_point' => (float) $row['BoilingPoint'],
                'isotopes' => (int) $row['NumberOfIsotopes'],
                'specific_heat' => (int) $row['SpecificHeat'],
                'shells' => (int) $row['NumberofShells'],
                'valence' => (int) $row['NumberofValence'],
            ]);

            ElementDiscovery::create([
               'element_id' => $element->id,
                'discoverer_id' => $discoverers->first(fn($guy) => $guy->name === $row['Discoverer'])->id ?? null,
                // TODO can make better
                'year' => Str::length($row['Year']) === 0 ? null : $row['Year']
            ]);
        });
    }
}
