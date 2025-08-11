<?php

namespace Tests\Feature\Controllers;

use App\Models\Element;

test('will hit the endpoint and return a success code', function () {
    $elements = Element::factory(3)->create();

    $this->getJson(route('elements.index'))
        ->assertOk()
        ->assertJson(['data' => $elements->toArray()]);
});
