<?php

namespace Tests\Jobs;

use App\Jobs\ImportElementDataJob;
use App\Models\ElementState;
use App\Models\Type;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $csvContent = Storage::disk()->get('Periodic_Table_of_Elements.csv');
    $lines = collect(explode(PHP_EOL, $csvContent));
    $this->headers = collect(explode(',', $lines->shift()));
    $lines->pop();
    $this->csvData = $lines->map(function ($row) {
        return $this->headers->combine(str_getcsv($row))->toArray();
    });
});

test('will insert element types into the database', function () {
    (new ImportElementDataJob)->handle();

    $this->csvData->filter(fn ($row) => isset($row['Type']))->each(function ($row) {
        $this->assertDatabaseHas('types', ['name' => $row['Type']]);
    });
});

test('will insert element states into the database', function () {
    $elementStates = $this->csvData->map(function ($row) {
        return $row['Phase'];
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
        return $row['Discoverer'];
    })->filter()->unique();

    (new ImportElementDataJob)->handle();

    $discoverers->each(function (string $discoverer) {
        $this->assertDatabaseHas('discoverers', [
            'name' => $discoverer,
        ]);
    });
});

test('will insert elements into the database', function () {
    (new ImportElementDataJob)->handle();

    $elementStates = ElementState::query()->get();
    $types = Type::query()->get();

    $elements = $this->csvData->map(function ($row) use ($elementStates, $types) {
        $elementStatesNames = $elementStates->pluck('id', 'name');
        $targetState = $elementStatesNames->filter(function ($id, $name) use ($row) {
           if ($row['Phase'] === $name) {
               return true;
           }
           return false;
        });

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
            'element_state_id' =>  $targetState->first(),
            'radioactive' => $row['Radioactive'] === 'yes',
            'natural' => $row['Natural'] === 'yes',
            'metal' => $row['Metal'] === 'yes',
            'metalloid' => $row['Metalloid'] === 'yes',
            'type_id' => $types->first(fn($type) => $type->name === $row['Type'])->id,
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
    });

    $elements->each(function ($element) {
        $this->assertDatabaseHas('elements', $element);
    });
});

test('will insert element discoveries into the database');
