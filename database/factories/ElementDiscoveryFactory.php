<?php

namespace Database\Factories;

use App\Models\Discoverer;
use App\Models\Element;
use App\Models\ElementDiscovery;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElementDiscoveryFactory extends Factory
{
    protected $model = ElementDiscovery::class;

    public function definition(): array
    {
        return [
            'element_id' => fn () => Element::factory(),
            'discoverer_id' => fn () => Discoverer::factory(),
            'year' => $this->faker->randomNumber(),
        ];
    }
}
