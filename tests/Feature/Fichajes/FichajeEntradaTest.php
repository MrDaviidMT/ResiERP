<?php

namespace Tests\Feature\Fichajes;

use App\Models\Fichaje;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FichajeEntradaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_clock_in(): void
    {
        $user = User::factory()->role('auxiliar')->create();

        $this->actingAs($user)
            ->post(route('fichajes.store'), ['user_id' => $user->id])
            ->assertSessionHas('success');

        $this->assertSame(1, Fichaje::where('user_id', $user->id)->whereNull('salida')->count());
    }

    public function test_user_cannot_clock_in_twice_without_clocking_out(): void
    {
        $user = User::factory()->role('auxiliar')->create();

        $this->actingAs($user)->post(route('fichajes.store'), ['user_id' => $user->id]);
        $this->actingAs($user)
            ->post(route('fichajes.store'), ['user_id' => $user->id])
            ->assertSessionHas('error');

        $this->assertSame(1, Fichaje::where('user_id', $user->id)->count());
    }

    public function test_user_can_clock_in_again_after_clocking_out(): void
    {
        $user = User::factory()->role('auxiliar')->create();
        Fichaje::create(['user_id' => $user->id, 'entrada' => now()->subHours(8), 'salida' => now()]);

        $this->actingAs($user)
            ->post(route('fichajes.store'), ['user_id' => $user->id])
            ->assertSessionHas('success');

        $this->assertSame(2, Fichaje::where('user_id', $user->id)->count());
    }
}
