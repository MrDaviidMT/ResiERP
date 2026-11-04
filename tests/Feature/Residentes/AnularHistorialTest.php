<?php

namespace Tests\Feature\Residentes;

use App\Enums\Role;
use App\Models\Alergia;
use App\Models\Diagnostico;
use App\Models\Log;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// El historial clínico no se borra, se anula con motivo
class AnularHistorialTest extends TestCase
{
    use RefreshDatabase;

    private function diagnosticoDe(User $autor, Residente $residente): Diagnostico
    {
        $this->actingAs($autor)->post(route('diagnosticos.store', $residente), [
            'nombre' => 'Diabetes tipo 2', 'tipo' => 'cronico', 'fecha_diagnostico' => '2026-01-10',
        ]);

        return Diagnostico::sole();
    }

    public function test_a_colleague_can_annul_with_a_reason_and_the_record_is_kept(): void
    {
        $residente = Residente::factory()->create();
        $autora = User::factory()->role(Role::Enfermero)->create();
        $companera = User::factory()->role(Role::Enfermero)->create();
        $diagnostico = $this->diagnosticoDe($autora, $residente);

        $this->actingAs($companera)
            ->post(route('diagnosticos.anular', $diagnostico), ['motivo' => 'Registrado en el residente equivocado'])
            ->assertSessionHas('success');

        $registro = Diagnostico::conAnulados()->sole();
        $this->assertTrue($registro->estaAnulado());
        $this->assertSame($companera->id, $registro->anulado_por);
        $this->assertSame('annulled', Log::where('loggable_type', Diagnostico::class)->latest('id')->first()->action);

        // En el historial ya no figura como vigente, pero se puede consultar con su motivo
        $this->actingAs($companera)->get(route('residentes.historial', $residente))
            ->assertSee('Mostrar anulados (1)')
            ->assertSee('Registrado en el residente equivocado');
    }

    public function test_a_reason_is_required(): void
    {
        $residente = Residente::factory()->create();
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $diagnostico = $this->diagnosticoDe($enfermera, $residente);

        $this->actingAs($enfermera)
            ->post(route('diagnosticos.anular', $diagnostico), ['motivo' => 'error'])
            ->assertSessionHasErrors('motivo');

        $this->assertFalse($diagnostico->fresh()->estaAnulado());
    }

    public function test_staff_without_permission_cannot_annul(): void
    {
        $residente = Residente::factory()->create();
        $diagnostico = $this->diagnosticoDe(User::factory()->role(Role::Enfermero)->create(), $residente);

        $this->actingAs(User::factory()->role(Role::Auxiliar)->create())
            ->post(route('diagnosticos.anular', $diagnostico), ['motivo' => 'Registrado en el residente equivocado'])
            ->assertForbidden();
    }

    public function test_clinical_records_can_no_longer_be_deleted(): void
    {
        $residente = Residente::factory()->create();
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $diagnostico = $this->diagnosticoDe($enfermera, $residente);

        $this->actingAs($enfermera)->delete("/diagnosticos/{$diagnostico->id}")->assertMethodNotAllowed();

        $this->assertSame(1, Diagnostico::count());
    }

    public function test_annulled_records_stop_counting_everywhere(): void
    {
        $residente = Residente::factory()->create();
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $this->actingAs($enfermera);
        $alergia = Alergia::create(['residente_id' => $residente->id, 'alergeno' => 'Penicilina', 'severidad' => 'grave', 'activa' => true]);

        $alergia->anular('Alergia registrada por error en este residente');

        $this->actingAs($enfermera)->get(route('residentes.show', $residente))->assertDontSee('Penicilina');
        $this->actingAs($enfermera)->get(route('dashboard'))->assertOk();
        $this->assertSame(0, Alergia::where('activa', true)->count());
        // Un registro anulado ya no se puede tocar: sus rutas responden 404
        $this->actingAs($enfermera)->post(route('alergias.toggle', $alergia))->assertNotFound();
    }
}
