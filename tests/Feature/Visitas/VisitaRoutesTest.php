<?php

namespace Tests\Feature\Visitas;

use App\Enums\Role;
use App\Models\Residente;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitaRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function datos(): array
    {
        return ['nombre_visitante' => 'Nombre corregido', 'documento' => '12345678Z', 'entrada' => now()->toDateTimeString()];
    }

    public function test_receptionist_can_edit_a_visit(): void
    {
        $recepcionista = User::factory()->role(Role::Recepcionista)->create();
        $visita = Visita::create(['residente_id' => Residente::factory()->create()->id, 'nombre_visitante' => 'Nombre', 'documento' => 'X', 'entrada' => now()]);

        $this->actingAs($recepcionista)->put(route('visitas.update', $visita), $this->datos())->assertSessionHas('success');

        $this->assertSame('Nombre corregido', $visita->fresh()->nombre_visitante);
    }

    public function test_staff_without_permission_cannot_edit_a_visit(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();
        $visita = Visita::create(['residente_id' => Residente::factory()->create()->id, 'nombre_visitante' => 'Nombre', 'documento' => 'X', 'entrada' => now()]);

        $this->actingAs($auxiliar)->put(route('visitas.update', $visita), $this->datos())->assertForbidden();

        $this->assertSame('Nombre', $visita->fresh()->nombre_visitante);
    }

    public function test_psychologist_sees_visits_but_cannot_register_them(): void
    {
        $psicologo = User::factory()->role(Role::Psicologo)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($psicologo)->get(route('visitas.index', $residente))->assertOk()->assertDontSee('Registrar Entrada');
        $this->actingAs($psicologo)->post(route('visitas.store', $residente), $this->datos())->assertForbidden();
    }

    public function test_only_the_director_can_annul_a_visit(): void
    {
        $enfermero = User::factory()->role(Role::Enfermero)->create();
        $director = User::factory()->role(Role::Director)->create();
        $visita = Visita::create(['residente_id' => Residente::factory()->create()->id, 'nombre_visitante' => 'Nombre', 'documento' => 'X', 'entrada' => now()]);
        $motivo = ['motivo' => 'Visita registrada en el residente equivocado'];

        $this->actingAs($enfermero)->post(route('visitas.anular', $visita), $motivo)->assertForbidden();
        $this->actingAs($director)->post(route('visitas.anular', $visita), $motivo)->assertRedirect();
        $this->actingAs($director)->delete("/visitas/{$visita->id}")->assertMethodNotAllowed();

        // El registro de accesos se conserva
        $this->assertSame(0, Visita::count());
        $this->assertSame(1, Visita::conAnulados()->count());
    }
}
