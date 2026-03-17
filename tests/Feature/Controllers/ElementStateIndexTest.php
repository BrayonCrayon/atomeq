<?php

use App\Models\ElementState;

describe("ElementStateIndex", function () {
    test("will retrieve all states from database", function () {
        $states = ElementState::factory()->count(4)->create();

        $this->getJson(route("states.index"))
            ->assertOk()
             ->assertJsonStructure([
                 "data" => [
                     [
                         'id',
                         'name'
                     ]
                 ]
             ])
            ->assertExactJson([
                 "data" => $states->map(fn ($item) => $item->only('id', 'name'))
            ]);
    });
});
