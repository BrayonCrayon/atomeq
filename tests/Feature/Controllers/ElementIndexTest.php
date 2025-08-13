<?php

namespace Tests\Feature\Controllers;

use App\Models\Element;

test('will hit the endpoint and return a success code', function () {
    $elements = Element::factory(2)->create();

    $callToGetElement = $this->getJson(route('elements.index'))
        ->assertOk();

    $elements->each(function ($element) use ($callToGetElement) {
        $callToGetElement->assertJsonFragment([
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
            'valence' => $element->valence
        ]);
    });
});
