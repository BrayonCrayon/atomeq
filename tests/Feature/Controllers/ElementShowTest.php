<?php

namespace Tests\Feature\Controllers;

test('will hit the endpont to retrieve a single element based on the id provided in the query params', function () {
    $this->getJson(route('elements.show'))->assertOk();
});
