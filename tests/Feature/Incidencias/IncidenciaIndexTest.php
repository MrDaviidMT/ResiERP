<?php

namespace Tests\Feature\Incidencias;

use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidenciaIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_and_urgent_incidents_are_listed_first(): void
    {
        $user = User::factory()->role('auxiliar')->create();
        $crear = function (string $titulo, string $estado, string $prioridad) {
            $incidencia = Incidencia::create(['categoria' => 'limpieza', 'titulo' => $titulo, 'prioridad' => $prioridad]);
            $incidencia->forceFill(['estado' => $estado])->save();
        };
        $crear('Resuelta urgente', 'resuelta', 'urgente');
        $crear('Pendiente leve', 'pendiente', 'leve');
        $crear('En proceso urgente', 'en_proceso', 'urgente');
        $crear('Pendiente urgente', 'pendiente', 'urgente');

        $this->actingAs($user)->get(route('incidencias.index'))
            ->assertOk()
            ->assertSeeInOrder(['Pendiente urgente', 'Pendiente leve', 'En proceso urgente', 'Resuelta urgente']);
    }
}
