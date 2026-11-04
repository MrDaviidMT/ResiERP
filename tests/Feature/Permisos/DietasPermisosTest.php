<?php

namespace Tests\Feature\Permisos;

use App\Enums\Role;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Fallo 11 / matriz 4.6: las dietas (con alergias alimentarias) las ve quien da de comer o cocina
class DietasPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_staff_can_see_the_diet(): void
    {
        $residente = Residente::factory()->create();

        $this->actingAs(User::factory()->role(Role::Cocinero)->create())
            ->get(route('dieta.index', $residente))->assertOk();
    }

    public function test_receptionist_cannot_see_the_diet(): void
    {
        $residente = Residente::factory()->create();
        $recepcionista = User::factory()->role(Role::Recepcionista)->create();

        $this->actingAs($recepcionista)->get(route('dieta.index', $residente))->assertForbidden();
        $this->actingAs($recepcionista)->get(route('residentes.show', $residente))
            ->assertOk()->assertDontSee(route('dieta.index', $residente));
    }
}
