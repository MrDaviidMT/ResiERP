<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HabitacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero' => $this->faker->unique()->numberBetween(100, 400),
            'tipo' => $this->faker->randomElement(['individual', 'doble']),
            'estado' => 'libre',
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
