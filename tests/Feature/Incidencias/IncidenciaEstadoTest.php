<?php

namespace Tests\Feature\Incidencias;

use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidenciaEstadoTest extends TestCase
{
    use RefreshDatabase;

    private function incidencia(string $estado): Incidencia
    {
        $incidencia = Incidencia::create([
            'categoria' => 'limpieza',
            'titulo' => 'Suelo mojado',
            'prioridad' => 'leve',
        ]);
        $incidencia->forceFill(['estado' => $estado])->save();

        return $incidencia;
    }

    public function test_allowed_transition_updates_status(): void
    {
        $user = User::factory()->role('auxiliar')->create();
        $incidencia = $this->incidencia('en_proceso');

        $this->actingAs($user)
            ->post(route('incidencias.estado', ['incidencia' => $incidencia->id, 'estado' => 'resuelta']))
            ->assertSessionHas('success');

        $this->assertSame('resuelta', $incidencia->fresh()->estado);
        $this->assertNotNull($incidencia->fresh()->fecha_resolucion);
    }

    public function test_disallowed_transition_is_rejected(): void
    {
        $user = User::factory()->role('auxiliar')->create();
        $incidencia = $this->incidencia('pendiente');

        $this->actingAs($user)
            ->post(route('incidencias.estado', ['incidencia' => $incidencia->id, 'estado' => 'resuelta']))
            ->assertSessionHas('error');

        $this->assertSame('pendiente', $incidencia->fresh()->estado);
    }

    public function test_unknown_status_returns_not_found(): void
    {
        $user = User::factory()->role('auxiliar')->create();
        $incidencia = $this->incidencia('pendiente');

        $this->actingAs($user)
            ->post("/incidencias/{$incidencia->id}/estado/hackeado")
            ->assertNotFound();

        $this->assertSame('pendiente', $incidencia->fresh()->estado);
    }
}
