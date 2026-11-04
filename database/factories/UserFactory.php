<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    // Rol por defecto si no se indica otro con role()
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->roles()->doesntExist()) {
                $user->assignRole(Role::Enfermero);
            }
        });
    }

    public function role(Role|string $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles([$role]));
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
