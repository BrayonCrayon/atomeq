<?php

namespace Tests\Feature\Controllers;

use App\Models\Element;

test('will hit the endpoint and return a success code', function () {
    $elements = Element::factory()->create();

    $this->getJson(route('elements.index'))
        ->assertOk()
        ->assertExactJson(['data' =>
            [
                [
                    'name' => $elements->name,
                    'atomicNumber' => $elements->atomic_number,
                    'atomicMass' => $elements->atomic_mass,
                    'symbol' => $elements->symbol,
                    'neutrons' => $elements->neutrons,
                    'protons' => $elements->protons,
                    'electrons' => $elements->electrons,
                    'period' => $elements->period,
                    'group' => $elements->group,
                    'elementStateId' => $elements->element_state_id,
                    'radioactive' => $elements->radioactive,
                    'natural' => $elements->natural,
                    'metal' => $elements->metal,
                    'metalloid' => $elements->metalloid,
                    'typeId' => $elements->type_id,
                    'atomicRadius' => $elements->atomic_radius,
                    'electronegativity' => $elements->electronegativity,
                    'firstIonization' => $elements->first_ionization,
                    'density' => $elements->density,
                    'meltingPoint' => $elements->melting_point,
                    'boilingPoint' => $elements->boiling_point,
                    'isotopes' => $elements->isotopes,
                    'specificHeat' => $elements->specific_heat,
                    'shells' => $elements->shells,
                    'valence' => $elements->valence
                ]
            ]
        ]);
});
