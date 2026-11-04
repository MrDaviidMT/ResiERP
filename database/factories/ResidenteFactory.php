<?php

namespace Database\Factories;

use App\Rules\DocumentoIdentidad;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResidenteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(),
            'apellidos' => $this->faker->lastName().' '.$this->faker->lastName(),
            'dni' => $this->dniValido(),
            'nacimiento' => $this->faker->date('Y-m-d', '-65 years'),
            'ingreso' => $this->faker->date('Y-m-d', '-2 years'),
            'grupo_sanguineo' => $this->faker->randomElement(['A+', 'A-', 'B+', 'O+', 'O-']),
            'habitacion_id' => null,
        ];
    }

    /** DNI con su letra de control, para que pase la validación real. */
    private function dniValido(): string
    {
        $numero = $this->faker->unique()->numberBetween(10000000, 99999999);

        return $numero.DocumentoIdentidad::letraDe($numero);
    }
}
