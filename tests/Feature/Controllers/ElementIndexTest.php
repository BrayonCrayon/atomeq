<?php

namespace Tests\Feature\Controllers;

use App\Models\Element;
use App\Models\ElementState;

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

test('will return relationships for each element', function () {
    $element = Element::factory()->create();
dd($element->state);
    $this->getJson(route('elements.index'))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $element->id,
            'elementStateId' => $element->element_state_id,
            'elementState' => $element->state,
            'typeId' => $element->type_id,
            'type' => $element->type
        ]);

});
