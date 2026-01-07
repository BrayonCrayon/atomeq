<?php

use App\Models\Type;

test('will access the type index route and retrieve a status of 200', function () {
    $type = Type::factory()->create();
    $response = $this->getJson(route('types.index'));

    $response->assertStatus(200);
    $response->assertExactJson([
        'data' => [
            [
                'id' => $type->id,
                'name' => $type->name,
                'parent_id' => $type->parent_id,
            ],
        ],
    ]);
});

