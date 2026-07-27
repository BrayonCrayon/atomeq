<?php

namespace Database\Factories;

use App\Models\Element;
use App\Models\Valency;
use Illuminate\Database\Eloquent\Factories\Factory;

class ValencyFactory extends Factory
{
    protected $model = Valency::class;

    public function definition(): array
    {
        return [
            'element_id' => Element::factory(),
            'valency' => $this->faker->numberBetween(1, 15),
            'is_default' => true
        ];
    }
}
