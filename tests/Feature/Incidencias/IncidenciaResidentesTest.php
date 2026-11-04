<?php

namespace Tests\Feature\Incidencias;

use App\Enums\Role;
use App\Models\Incidencia;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// El formulario de incidencias no expone el DNI ni la lista de residentes a quien no puede verlos
class IncidenciaResidentesTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_selector_never_shows_the_dni(): void
    {
        $enfermero = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create(['nombre' => 'Carmen']);

        $this->actingAs($enfermero)->get(route('incidencias.index'))
            ->assertSee('Carmen')
            ->assertDontSee($residente->dni);
    }

    public function test_maintenance_staff_do_not_get_the_resident_list(): void
    {
        $mantenimiento = User::factory()->role(Role::Mantenimiento)->create();
        Residente::factory()->create(['nombre' => 'Carmen']);

        $this->actingAs($mantenimiento)->get(route('incidencias.index'))
            ->assertOk()
            ->assertDontSee('residente_id_input')
            ->assertDontSee('Carmen');
    }

    public function test_staff_without_resident_access_cannot_link_or_unlink_a_resident(): void
    {
        $mantenimiento = User::factory()->role(Role::Mantenimiento)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($mantenimiento)->post(route('incidencias.store'), [
            'categoria' => 'infraestructura', 'titulo' => 'Persiana rota', 'prioridad' => 'leve', 'residente_id' => $residente->id,
        ]);
        $this->assertNull(Incidencia::sole()->residente_id);

        $incidencia = Incidencia::sole();
        $incidencia->forceFill(['residente_id' => $residente->id])->save();
        $this->actingAs($mantenimiento)->put(route('incidencias.update', $incidencia), [
            'categoria' => 'infraestructura', 'titulo' => 'Persiana rota (revisada)', 'prioridad' => 'leve',
        ])->assertSessionHas('success');

        $this->assertSame($residente->id, $incidencia->fresh()->residente_id);
    }
}
