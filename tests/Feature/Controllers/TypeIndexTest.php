<?php

use App\Models\Type;

test('will access the type index route and retrieve a status of 200', function () {
    $types = Type::get()->map(fn ($type) => ['id' => $type->id, 'name' => $type->name, 'parentId' => $type->parent_id ]);

    $this->getJson(route('types.index'))
        ->assertStatus(200)
        ->assertExactJson([
            'data' => $types->toArray(),
        ]);
});
