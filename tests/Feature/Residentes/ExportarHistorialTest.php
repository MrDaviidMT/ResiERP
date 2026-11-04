<?php

namespace Tests\Feature\Residentes;

use App\Enums\Role;
use App\Models\Alergia;
use App\Models\Diagnostico;
use App\Models\Informe;
use App\Models\Log;
use App\Models\Medicacion;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// L4 (auditoría): derecho de acceso a la historia clínica (Ley 41/2002 art. 18, RGPD art. 15)
class ExportarHistorialTest extends TestCase
{
    use RefreshDatabase;

    private function residenteConHistorial(): Residente
    {
        $residente = Residente::factory()->create(['nombre' => 'Carmen', 'apellidos' => 'Gil Soto']);
        $enfermera = User::factory()->role(Role::Enfermero)->create(['name' => 'Lucía Soto']);
        $this->actingAs($enfermera);

        Diagnostico::create(['residente_id' => $residente->id, 'nombre' => 'Diabetes tipo 2', 'tipo' => 'cronico', 'fecha_diagnostico' => '2026-01-10']);
        Alergia::create(['residente_id' => $residente->id, 'alergeno' => 'Penicilina', 'severidad' => 'grave', 'activa' => true]);
        Medicacion::create(['residente_id' => $residente->id, 'medicamento' => 'Metformina', 'dosis' => '850 mg']);
        $informe = Informe::create(['titulo' => 'Valoración inicial', 'tipo' => 'enfermeria', 'contenido' => 'Texto del informe']);
        $informe->residentes()->attach($residente->id);
        Diagnostico::create(['residente_id' => $residente->id, 'nombre' => 'Asma mal registrada', 'tipo' => 'cronico', 'fecha_diagnostico' => '2026-02-01'])
            ->anular('Registrada en el residente equivocado');

        return $residente;
    }

    public function test_the_export_contains_the_whole_history_including_annulled_records(): void
    {
        $residente = $this->residenteConHistorial();

        $html = $this->actingAs(User::factory()->role(Role::Director)->create())
            ->get(route('residentes.historial.exportar', $residente))
            ->assertOk()
            ->getContent();

        foreach (['Carmen', 'Gil Soto', 'Diabetes tipo 2', 'Penicilina', 'Metformina', 'Valoración inicial', 'Lucía Soto'] as $esperado) {
            $this->assertStringContainsString($esperado, $html);
        }
        // Lo anulado también forma parte de la historia, marcado como tal
        $this->assertStringContainsString('Asma mal registrada', $html);
        $this->assertStringContainsString('Registrada en el residente equivocado', $html);
    }

    public function test_the_export_is_audited_as_an_access_to_the_record(): void
    {
        $residente = $this->residenteConHistorial();
        $director = User::factory()->role(Role::Director)->create();

        $this->actingAs($director)->get(route('residentes.historial.exportar', $residente));

        $log = Log::where('action', 'exported')->sole();
        $this->assertSame($director->id, $log->user_id);
        $this->assertSame('historia_clinica', $log->changes['tipo']);
        $this->assertSame($residente->id, $log->loggable_id);
    }

    public function test_only_staff_with_permission_can_export_it(): void
    {
        $residente = $this->residenteConHistorial();

        foreach ([Role::Auxiliar, Role::Cocinero, Role::Recepcionista] as $rol) {
            $this->actingAs(User::factory()->role($rol)->create())
                ->get(route('residentes.historial.exportar', $residente))->assertForbidden();
        }

        $this->actingAs(User::factory()->role(Role::TrabajadorSocial)->create())
            ->get(route('residentes.historial.exportar', $residente))->assertOk();
    }
}
