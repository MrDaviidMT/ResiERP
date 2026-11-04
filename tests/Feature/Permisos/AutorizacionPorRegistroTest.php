<?php

namespace Tests\Feature\Permisos;

use App\Enums\Role;
use App\Models\Fichaje;
use App\Models\Incidencia;
use App\Models\Informe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Autorización que depende del dato concreto (categoría, propietario, tipo).
class AutorizacionPorRegistroTest extends TestCase
{
    use RefreshDatabase;

    private function incidencia(string $categoria): Incidencia
    {
        return Incidencia::create(['categoria' => $categoria, 'titulo' => 'Avería', 'prioridad' => 'leve']);
    }

    public function test_cannot_change_the_status_of_an_incident_outside_your_categories(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();
        $incidencia = $this->incidencia('infraestructura');

        $this->actingAs($auxiliar)->post(route('incidencias.estado', ['incidencia' => $incidencia, 'estado' => 'en_proceso']));

        $this->assertSame('pendiente', $incidencia->fresh()->estado);
    }

    public function test_cannot_edit_an_incident_outside_your_categories(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();
        $incidencia = $this->incidencia('infraestructura');

        $this->actingAs($auxiliar)->get(route('incidencias.edit', $incidencia))->assertForbidden();
        $this->actingAs($auxiliar)->put(route('incidencias.update', $incidencia), [
            'categoria' => 'infraestructura', 'titulo' => 'Cambiado', 'prioridad' => 'leve',
        ])->assertForbidden();

        $this->assertSame('Avería', $incidencia->fresh()->titulo);
    }

    public function test_cannot_see_another_employees_time_entry(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();
        $ajeno = Fichaje::create(['user_id' => User::factory()->create()->id, 'entrada' => now()->subHours(8), 'salida' => now()]);

        $this->actingAs($auxiliar)->get(route('fichajes.show', $ajeno))->assertForbidden();
        $this->actingAs($auxiliar)->post(route('fichajes.salida', $ajeno))->assertForbidden();
    }

    public function test_cannot_clock_in_on_behalf_of_another_employee(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();
        $otro = User::factory()->create();

        $this->actingAs($auxiliar)->post(route('fichajes.store'), ['user_id' => $otro->id]);

        $this->assertSame(0, Fichaje::where('user_id', $otro->id)->count());
    }

    public function test_author_cannot_change_a_report_to_a_type_they_cannot_create(): void
    {
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $informe = tap((new Informe(['titulo' => 'Informe', 'tipo' => 'enfermeria', 'contenido' => 'Texto']))->forceFill(['user_id' => $enfermera->id]))->save();

        $this->actingAs($enfermera)->put(route('informes.update', $informe), [
            'titulo' => 'Informe', 'tipo' => 'psicologico', 'contenido' => 'Texto',
        ]);

        $this->assertSame('enfermeria', $informe->fresh()->tipo);
    }
}
