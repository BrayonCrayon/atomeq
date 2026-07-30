<?php

namespace Tests\Feature\Controllers;

use App\Models\Element;

test('will hit the endpoint and return a success code', function () {
    $formattedElements = Element::get()->map(function ($element) {
        return [
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
            'valence' => $element->valence
        ];
    });

    $this->getJson(route('elements.index'))
        ->assertOk()
        ->assertExactJson([
            'data' => $formattedElements->toArray()
        ]);
});

test('will return relationships for each element', function () {
    $element = Element::first();
    $relations = ['relations' => ['type', 'state']];

    $this->getJson(route('elements.index', $relations))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $element->id,
            'elementStateId' => $element->element_state_id,
            'elementState' => [
                'id' => $element->state->id,
                'name' => $element->state->name,
            ],
            'typeId' => $element->type_id,
            'type' => [
                'id' => $element->type->id,
                'name' => $element->type->name,
                'parentId' => $element->type->parent_id,
            ],
        ]);

});
