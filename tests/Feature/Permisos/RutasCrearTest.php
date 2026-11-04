<?php

namespace Tests\Feature\Permisos;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Las rutas de recurso divididas por permiso deben registrar "create" antes que "{id}"
class RutasCrearTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_open_the_new_employee_form(): void
    {
        $this->actingAs(User::factory()->role(Role::Director)->create())
            ->get(route('users.create'))->assertOk();
    }

    public function test_activity_manager_can_open_the_new_activity_form(): void
    {
        $this->actingAs(User::factory()->role(Role::Psicologo)->create())
            ->get(route('actividades.create'))->assertOk();
    }
}
