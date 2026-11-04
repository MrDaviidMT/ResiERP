<?php

namespace Tests\Feature\Permisos;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Habitacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Ver habitaciones, todo el personal asistencial; editarlas, solo dirección
class HabitacionesPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_auxiliary_sees_rooms_but_cannot_edit_them(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();
        $habitacion = Habitacion::factory()->create();

        $this->actingAs($auxiliar)->get(route('habitaciones.index'))->assertOk()->assertDontSee('Editar habitación');
        $this->actingAs($auxiliar)->put(route('habitaciones.update', $habitacion), ['observaciones' => 'x'])->assertForbidden();
    }

    public function test_maintenance_can_still_toggle_maintenance_mode(): void
    {
        $mantenimiento = User::factory()->role(Role::Mantenimiento)->create();

        $this->assertFalse($mantenimiento->can(Permission::HabitacionesEditar));
        $this->assertTrue($mantenimiento->can(Permission::HabitacionesMantenimiento));
    }
}
