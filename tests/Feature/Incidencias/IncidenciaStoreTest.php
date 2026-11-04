<?php

namespace Tests\Feature\Incidencias;

use App\Models\Habitacion;
use App\Models\Incidencia;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidenciaStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_incident_ignores_status_and_resolution_date_from_request(): void
    {
        $user = User::factory()->role('auxiliar')->create();

        $this->actingAs($user)->post(route('incidencias.store'), [
            'categoria' => 'limpieza',
            'titulo' => 'Suelo mojado en el pasillo',
            'prioridad' => 'leve',
            'estado' => 'resuelta',
            'fecha_resolucion' => '2020-01-01 00:00:00',
        ])->assertRedirect();

        $incidencia = Incidencia::sole();
        $this->assertSame('pendiente', $incidencia->fresh()->estado);
        $this->assertNull($incidencia->fresh()->fecha_resolucion);
    }

    public function test_infrastructure_incident_in_occupied_room_is_saved_with_a_success_message(): void
    {
        $user = User::factory()->role('mantenimiento')->create();
        $habitacion = Habitacion::factory()->create(['estado' => 'ocupada']);
        Residente::factory()->create(['habitacion_id' => $habitacion->id]);

        $this->actingAs($user)->post(route('incidencias.store'), [
            'categoria' => 'infraestructura',
            'titulo' => 'Radiador roto',
            'prioridad' => 'grave',
            'habitacion_id' => $habitacion->id,
        ])->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertSame(1, Incidencia::count());
        $this->assertSame('ocupada', $habitacion->fresh()->estado);
    }

    public function test_author_can_see_their_own_personal_incident_but_not_others(): void
    {
        $autor = User::factory()->role('auxiliar')->create();
        $companero = User::factory()->role('auxiliar')->create();

        $this->actingAs($autor)->post(route('incidencias.store'), [
            'categoria' => 'personal', 'titulo' => 'Conflicto en el turno de noche', 'prioridad' => 'grave', 'user_id' => $companero->id,
        ]);
        $incidencia = Incidencia::sole();

        $this->assertSame($autor->id, $incidencia->user_id);
        $this->actingAs($autor)->get(route('incidencias.show', $incidencia))->assertOk();
        $this->actingAs($autor)->get(route('incidencias.index'))->assertSee('Conflicto en el turno de noche');
        $this->actingAs($companero)->get(route('incidencias.show', $incidencia))->assertForbidden();
        $this->actingAs($companero)->get(route('incidencias.index'))->assertDontSee('Conflicto en el turno de noche');
    }
}
