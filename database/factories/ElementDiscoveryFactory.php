<?php

namespace Database\Factories;

use App\Models\Discoverer;
use App\Models\Element;
use Illuminate\Database\Eloquent\Factories\Factory;
class ElementDiscoveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'element_id' => fn () => Element::factory()->create(),
            'discoverer_id' => fn () => Discoverer::factory()->create(),
            'year' => $this->faker->year,
        ];
    }
}
