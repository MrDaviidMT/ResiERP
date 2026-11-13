<?php

namespace Tests\Feature\Residentes;

use App\Enums\Role;
use App\Models\Alergia;
use App\Models\CuidadoDiario;
use App\Models\Diagnostico;
use App\Models\Log;
use App\Models\Medicacion;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Cada registro clínico identifica a su autor, y solo el autor corrige su contenido
class AutorRegistroClinicoTest extends TestCase
{
    use RefreshDatabase;

    private function diagnostico(): array
    {
        return ['nombre' => 'Hipertensión', 'tipo' => 'cronico', 'fecha_diagnostico' => '2026-01-10'];
    }

    public function test_author_is_taken_from_the_session_and_cannot_be_spoofed(): void
    {
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $otra = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($enfermera)->post(route('diagnosticos.store', $residente), $this->diagnostico() + ['user_id' => $otra->id]);
        $this->actingAs($enfermera)->post(route('medicacion.store', $residente->id), ['medicamento' => 'Enalapril', 'dosis' => '5 mg', 'user_id' => $otra->id]);

        $this->assertSame($enfermera->id, Diagnostico::sole()->user_id);
        $this->assertSame($enfermera->id, Medicacion::sole()->user_id);
    }

    public function test_only_the_author_can_correct_a_diagnosis(): void
    {
        $autora = User::factory()->role(Role::Enfermero)->create();
        $companera = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();
        $this->actingAs($autora)->post(route('diagnosticos.store', $residente), $this->diagnostico());
        $diagnostico = Diagnostico::sole();

        $cambio = ['nombre' => 'Hipertensión arterial', 'tipo' => 'cronico', 'fecha_diagnostico' => '2026-01-10'];
        $this->actingAs($companera)->put(route('diagnosticos.update', $diagnostico), $cambio)->assertForbidden();
        $this->assertSame('Hipertensión', $diagnostico->fresh()->nombre);

        $this->actingAs($autora)->put(route('diagnosticos.update', $diagnostico), $cambio)->assertSessionHas('success');
        $this->assertSame('Hipertensión arterial', $diagnostico->fresh()->nombre);
    }

    // Un cuidado editado por otra persona seguía figurando con el autor original
    public function test_only_the_author_can_correct_a_daily_care_record(): void
    {
        $autor = User::factory()->role(Role::Auxiliar)->create();
        $companero = User::factory()->role(Role::Auxiliar)->create();
        $residente = Residente::factory()->create();
        $this->actingAs($autor)->post(route('cuidados_diarios.store', $residente), ['fecha' => '2026-10-29', 'comio_bien' => '1']);
        $cuidado = CuidadoDiario::sole();

        $this->assertSame($autor->id, $cuidado->user_id);
        $this->actingAs($companero)->put(route('cuidados_diarios.update', $cuidado), ['fecha' => '2026-10-29', 'observaciones' => 'Cambiado'])->assertForbidden();
        $this->assertNull($cuidado->fresh()->observaciones);
    }

    public function test_edit_button_is_only_shown_to_the_author(): void
    {
        $autora = User::factory()->role(Role::Enfermero)->create();
        $companera = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();
        $this->actingAs($autora)->post(route('diagnosticos.store', $residente), $this->diagnostico());

        $this->actingAs($autora)->get(route('residentes.historial', $residente))->assertSee('Editar diagnóstico');
        $this->actingAs($companera)->get(route('residentes.historial', $residente))->assertDontSee('Editar diagnóstico');
    }

    // La restricción al autor es solo para corregir el contenido. Marcar una condición como activa o
    // resuelta es seguimiento clínico que hace quien atiende, no quien la escribió, y queda auditado.
    public function test_a_colleague_may_toggle_the_status_of_a_record_she_did_not_write(): void
    {
        $autora = User::factory()->role(Role::Enfermero)->create();
        $companera = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();
        $this->actingAs($autora)->post(route('diagnosticos.store', $residente), $this->diagnostico());
        $diagnostico = Diagnostico::sole();
        $this->assertTrue((bool) $diagnostico->activo);

        // Corregir el contenido sigue vedado a quien no es la autora
        $this->actingAs($companera)->put(route('diagnosticos.update', $diagnostico), $this->diagnostico())->assertForbidden();

        // Cambiar el estado sí se permite, y el cambio se audita a nombre de quien lo hace
        $this->actingAs($companera)->post(route('diagnosticos.toggle', $diagnostico))->assertSessionHas('success');
        $this->assertFalse((bool) $diagnostico->fresh()->activo);
        $cambio = Log::where('action', 'updated')->where('loggable_id', $diagnostico->id)->latest('id')->first();
        $this->assertSame($companera->id, $cambio->user_id);
    }

    public function test_a_colleague_may_toggle_an_allergy_she_did_not_write(): void
    {
        $autora = User::factory()->role(Role::Enfermero)->create();
        $companera = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();
        $this->actingAs($autora)->post(route('alergias.store', $residente), ['alergeno' => 'Penicilina', 'severidad' => 'grave']);
        $alergia = Alergia::sole();

        $this->actingAs($companera)->post(route('alergias.toggle', $alergia))->assertSessionHas('success');
        $this->assertNotSame((bool) $alergia->activa, (bool) $alergia->fresh()->activa);
    }
}
