<?php

namespace Tests\Feature\Permisos;

use App\Enums\Role;
use App\Models\Familiar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Borrar familiares es solo del director
class FamiliaresPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_worker_can_edit_but_not_delete_relatives(): void
    {
        $ts = User::factory()->role(Role::TrabajadorSocial)->create();
        $familiar = Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000']);

        $this->actingAs($ts)->get(route('familiares.edit', $familiar))->assertOk();
        $this->actingAs($ts)->delete(route('familiares.destroy', $familiar))->assertForbidden();

        $this->assertNotNull($familiar->fresh());
    }

    public function test_director_can_delete_an_unlinked_relative(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $familiar = Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000']);

        $this->actingAs($director)->delete(route('familiares.destroy', $familiar))->assertRedirect();

        $this->assertNull($familiar->fresh());
    }
}
