<?php

namespace Database\Factories;

use App\Models\ElementState;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElementStateFactory extends Factory
{

    protected $model = ElementState::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name()
        ];
    }
}
