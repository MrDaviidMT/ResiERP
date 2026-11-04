<?php

namespace Tests\Feature\Incidencias;

use App\Enums\Role;
use App\Models\Actividad;
use App\Models\Incidencia;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Incidencias y actividades se anulan con motivo en lugar de borrarse
class AnularIncidenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_annul_an_incident_and_it_is_kept(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $this->actingAs($director)->post(route('incidencias.store'), ['categoria' => 'limpieza', 'titulo' => 'Suelo mojado', 'prioridad' => 'leve']);

        $this->actingAs($director)->post(route('incidencias.anular', Incidencia::sole()), ['motivo' => 'Incidencia duplicada por error'])
            ->assertSessionHas('success');

        $this->assertSame(0, Incidencia::count());
        $this->assertSame(1, Incidencia::conAnulados()->count());
        $this->actingAs($director)->get(route('incidencias.index'))->assertSee('Incidencia duplicada por error');
    }

    public function test_only_staff_with_permission_can_annul_and_deletion_no_longer_exists(): void
    {
        $incidencia = Incidencia::create(['categoria' => 'limpieza', 'titulo' => 'Suelo mojado', 'prioridad' => 'leve']);
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();

        $this->actingAs($auxiliar)->post(route('incidencias.anular', $incidencia), ['motivo' => 'Incidencia duplicada por error'])->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Director)->create())->delete("/incidencias/{$incidencia->id}")->assertMethodNotAllowed();

        $this->assertSame(1, Incidencia::count());
    }

    public function test_annulled_personal_incidents_stay_hidden_from_other_staff(): void
    {
        $autor = User::factory()->role(Role::Auxiliar)->create();
        $this->actingAs($autor)->post(route('incidencias.store'), ['categoria' => 'personal', 'titulo' => 'Conflicto en el turno', 'prioridad' => 'grave']);
        $this->actingAs(User::factory()->role(Role::Director)->create())
            ->post(route('incidencias.anular', Incidencia::sole()), ['motivo' => 'Registrada por error, se repite']);

        $this->actingAs(User::factory()->role(Role::Auxiliar)->create())->get(route('incidencias.index'))->assertDontSee('Conflicto en el turno');
        $this->actingAs($autor)->get(route('incidencias.index'))->assertSee('Conflicto en el turno');
    }

    public function test_activities_are_annulled_keeping_their_participants(): void
    {
        $psicologa = User::factory()->role(Role::Psicologo)->create();
        $this->actingAs($psicologa);
        $actividad = Actividad::create(['user_id' => $psicologa->id, 'titulo' => 'Taller de memoria', 'fecha' => now(), 'hora' => '10:00']);
        $actividad->residentes()->attach(Residente::factory()->create()->id);

        $this->post(route('actividades.anular', $actividad), ['motivo' => 'Se canceló por falta de monitor'])->assertRedirect();
        $this->delete("/actividades/{$actividad->id}")->assertMethodNotAllowed();

        $anulada = Actividad::conAnulados()->sole();
        $this->assertTrue($anulada->estaAnulado());
        $this->assertSame(1, $anulada->residentes()->count());
        $this->get(route('actividades.index'))->assertSee('Se canceló por falta de monitor');
    }
}
