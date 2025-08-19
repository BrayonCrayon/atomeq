<?php

namespace Tests\Feature\Controllers;

use App\Models\Discoverer;
use App\Models\Element;

test('will reject a request without element id', function () {
    $this->getJson(route('elements.show', ['element' => -2982929292]))
        ->assertNotFound();
});

test('will hit the endpoint to retrieve a single element based on the id provided in the query params', function () {
    $element = Element::factory()->create();

    $this->getJson(route('elements.show', ['element' => $element->name]))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'id' => $element->id,
                'name' => $element->name,
                'atomicNumber' => $element->atomic_number,
                'atomicMass' => $element->atomic_mass,
                'symbol' => $element->symbol,
                'neutrons' => $element->neutrons,
                'protons' => $element->protons,
                'electrons' => $element->electrons,
                'period' => $element->period,
                'group' => $element->group,
                'elementStateId' => $element->element_state_id,
                'radioactive' => $element->radioactive,
                'natural' => $element->natural,
                'metal' => $element->metal,
                'metalloid' => $element->metalloid,
                'typeId' => $element->type_id,
                'atomicRadius' => $element->atomic_radius,
                'electronegativity' => $element->electronegativity,
                'firstIonization' => $element->first_ionization,
                'density' => $element->density,
                'meltingPoint' => $element->melting_point,
                'boilingPoint' => $element->boiling_point,
                'isotopes' => $element->isotopes,
                'specificHeat' => $element->specific_heat,
                'shells' => $element->shells,
                'valence' => $element->valence,
            ],
        ]);
});

test("will return discoverers when retrieving an element data", function () {
    $element = Element::factory()->hasDiscoverers()->create();

    $response = $this->getJson(
        route('elements.show', ['element' => $element->name, 'relations' => ['discoverers']])
    )->assertOk();

    $element->discoverers->each(function (Discoverer $discoverer) use ($response) {
        $response->assertJsonFragment([
            'id' => $discoverer->id,
            'name' => $discoverer->name,
            'year' => $discoverer->pivot_year, //TODO: not populating correctly, please to fix
        ]);
    });
});
