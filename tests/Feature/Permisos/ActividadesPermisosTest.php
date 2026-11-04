<?php

namespace Tests\Feature\Permisos;

use App\Enums\Role;
use App\Models\Actividad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Enfermería, auxiliares y fisioterapia ven las actividades; gestionarlas, Dir, Psi y TS
class ActividadesPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_auxiliary_sees_activities_without_management_buttons(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();
        $actividad = Actividad::create(['user_id' => $auxiliar->id, 'titulo' => 'Taller de memoria', 'fecha' => now(), 'hora' => '10:00']);

        $this->actingAs($auxiliar)->get(route('actividades.index'))
            ->assertOk()->assertSee('Taller de memoria')->assertDontSee(route('actividades.create'));
        $this->actingAs($auxiliar)->get(route('actividades.show', $actividad))
            ->assertOk()->assertDontSee(route('actividades.edit', $actividad));
        $this->actingAs($auxiliar)->get(route('actividades.create'))->assertForbidden();
    }

    public function test_psychologist_can_manage_activities(): void
    {
        $psicologo = User::factory()->role(Role::Psicologo)->create();

        $this->actingAs($psicologo)->get(route('actividades.index'))->assertSee(route('actividades.create'));
    }
}
