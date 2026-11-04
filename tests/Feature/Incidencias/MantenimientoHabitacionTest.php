<?php

namespace Tests\Feature\Incidencias;

use App\Enums\Role;
use App\Models\Habitacion;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Fallo existente desde el código original: resolver la última incidencia grave nunca liberaba la habitación
class MantenimientoHabitacionTest extends TestCase
{
    use RefreshDatabase;

    private User $director;

    protected function setUp(): void
    {
        parent::setUp();
        $this->director = User::factory()->role(Role::Director)->create();
    }

    private function averia(Habitacion $habitacion, string $prioridad = 'grave'): Incidencia
    {
        $this->actingAs($this->director)->post(route('incidencias.store'), [
            'categoria' => 'infraestructura', 'titulo' => 'Fuga de agua', 'prioridad' => $prioridad, 'habitacion_id' => $habitacion->id,
        ]);

        return Incidencia::latest('id')->first();
    }

    private function resolver(Incidencia $incidencia): void
    {
        $this->post(route('incidencias.estado', ['incidencia' => $incidencia, 'estado' => 'en_proceso']));
        $this->post(route('incidencias.estado', ['incidencia' => $incidencia, 'estado' => 'resuelta']));
    }

    public function test_resolving_the_last_blocking_incident_frees_the_room(): void
    {
        $habitacion = Habitacion::factory()->create(['estado' => 'libre']);
        $incidencia = $this->averia($habitacion);
        $this->assertSame('mantenimiento', $habitacion->fresh()->estado);

        $this->resolver($incidencia);

        $this->assertSame('libre', $habitacion->fresh()->estado);
    }

    public function test_room_stays_blocked_while_another_blocking_incident_is_active(): void
    {
        $habitacion = Habitacion::factory()->create(['estado' => 'libre']);
        $primera = $this->averia($habitacion);
        $this->averia($habitacion, 'urgente');

        $this->resolver($primera);

        $this->assertSame('mantenimiento', $habitacion->fresh()->estado);
    }

    public function test_annulling_the_blocking_incident_frees_the_room(): void
    {
        $habitacion = Habitacion::factory()->create(['estado' => 'libre']);
        $incidencia = $this->averia($habitacion);

        $this->post(route('incidencias.anular', $incidencia), ['motivo' => 'Avería registrada en la habitación equivocada']);

        $this->assertSame('libre', $habitacion->fresh()->estado);
    }

    public function test_manual_maintenance_is_not_lifted_by_resolving_an_incident(): void
    {
        $habitacion = Habitacion::factory()->create(['estado' => 'libre']);
        $this->actingAs($this->director)->post(route('habitaciones.mantenimiento', $habitacion->id));
        $incidencia = $this->averia($habitacion);

        $this->resolver($incidencia);

        $this->assertSame('mantenimiento', $habitacion->fresh()->estado);
        $this->assertTrue($habitacion->fresh()->mantenimiento_manual);
    }
}
