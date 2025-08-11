<?php

namespace Tests\Feature\Controllers;

use App\Models\Element;

test('will hit the endpoint and return a success code', function () {
    $elements = Element::factory()->create();

    $this->getJson(route('elements.index'))
        ->assertOk()
        ->assertJson(['data' => [
            ['atomicNumber' => $elements->atomic_number]
        ]], true) ;
});
