<?php

use App\Models\Type;

test('will access the type index route and retrieve a status of 200', function () {
    $type = Type::factory()->create();

    $this->getJson(route('types.index'))
        ->assertStatus(200)
        ->assertExactJson([
            'data' => [
                [
                    'id' => $type->id,
                    'name' => $type->name,
                    'parent_id' => $type->parent_id,
                ],
            ],
        ]);
});
