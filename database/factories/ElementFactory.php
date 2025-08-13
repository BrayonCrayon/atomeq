<?php

namespace Database\Factories;

use App\Models\Element;
use App\Models\ElementState;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElementFactory extends Factory
{
    protected $model = Element::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'atomic_number' => $this->faker->numberBetween(0, 100),
            'atomic_mass' => $this->faker->randomFloat(3, 0, 1),
            'symbol' => $this->faker->unique()->lexify('??'),
            'neutrons' => $this->faker->numberBetween(0, 100),
            'protons' => $this->faker->numberBetween(0, 100),
            'electrons' => $this->faker->numberBetween(0, 100),
            'period' => $this->faker->numberBetween(0, 100),
            'group' => $this->faker->numberBetween(0, 100),
            'element_state_id' => fn () => ElementState::factory(),
            'radioactive' => $this->faker->boolean(),
            'natural' => $this->faker->boolean(),
            'metal' => $this->faker->boolean(),
            'metalloid' => $this->faker->boolean(),
            'type_id' => fn () => Type::factory(),
            'atomic_radius' => $this->faker->randomFloat(3, 0, 1),
            'electronegativity' => $this->faker->randomFloat(3, 0, 1),
            'first_ionization' => $this->faker->randomFloat(3, 0, 1),
            'density' => $this->faker->bothify('?###'),
            'melting_point' => $this->faker->randomFloat(3, 10, 200),
            'boiling_point' => $this->faker->randomFloat(3, 10, 200),
            'isotopes' => $this->faker->numberBetween(0, 100),
            'specific_heat' => $this->faker->numberBetween(0, 100),
            'shells' => $this->faker->numberBetween(0, 100),
            'valence' => $this->faker->numberBetween(0, 100),
        ];
    }
}
