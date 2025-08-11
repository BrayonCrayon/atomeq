<?php

namespace Tests\Feature\Controllers;

test('will hit the endpoint and return a success code', function () {
    $this->getJson(route('elements.index'))
        ->assertOk();
});
