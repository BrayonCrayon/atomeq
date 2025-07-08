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

        $discoverers = $data->map(fn ($line) => ['name' => $line['Discoverer']])
            ->filter()
            ->unique()->toArray();
        Discoverer::insert($discoverers);

        $elementStates = ElementState::all();
        $elementTypes = Type::all();
        $discoverers = Discoverer::query()->get();

        $elementsToInsert = [];
        $elementDiscoveries = collect();
        $data->each(function ($row) use ($elementStates, $elementTypes, $discoverers, &$elementsToInsert, $elementDiscoveries) {
            $elementsToInsert[] = $this->rowToElementInsert($elementStates, $elementTypes, $row);

            $elementDiscoveries->push([
                'element_name' => $row['Element'],
                'discoverer_id' => $discoverers->first(fn ($guy) => $guy->name === $row['Discoverer'])->id ?? null,
                'year' => Str::length($row['Year']) === 0 ? null : $row['Year'],
            ]);
        });

        Element::insert($elementsToInsert);

        $realElements = Element::all();
        $discoveriesToEnter = $elementDiscoveries->map(fn (array $discovery) => [
            'element_id' => $realElements->first(fn (Element $element) => $element->name === $discovery['element_name'])->id,
            'discoverer_id' => $discovery['discoverer_id'],
            'year' => $discovery['year'],
        ]);
        ElementDiscovery::insert($discoveriesToEnter->toArray());
    }

    public function rowToElementInsert($elementStates, $elementTypes, $row): array //
    {
        return [
            'name' => $row['Element'],
            'atomic_number' => (int) $row['AtomicNumber'],
            'atomic_mass' => (float) $row['AtomicMass'],
            'symbol' => $row['Symbol'],
            'neutrons' => (int) $row['NumberofNeutrons'],
            'protons' => (int) $row['NumberofProtons'],
            'electrons' => (int) $row['NumberofElectrons'],
            'period' => (int) $row['Period'],
            'group' => (int) $row['Group'],
            'element_state_id' => $elementStates->first(fn ($item) => $item->name === $row['Phase'])->id,
            'radioactive' => $row['Radioactive'] === 'yes',
            'natural' => $row['Natural'] === 'yes',
            'metal' => $row['Metal'] === 'yes',
            'metalloid' => $row['Metalloid'] === 'yes',
            'type_id' => $elementTypes->first(fn ($item) => $item->name === $row['Type'])->id,
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
        ];
    }
}
