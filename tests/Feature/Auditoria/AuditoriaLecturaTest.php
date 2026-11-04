<?php

namespace Tests\Feature\Auditoria;

use App\Enums\Role;
use App\Models\Diagnostico;
use App\Models\Informe;
use App\Models\Log;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Registro de consultas a datos sensibles: quién abrió el historial, la medicación o un informe
class AuditoriaLecturaTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_sensitive_screens_is_audited(): void
    {
        $enfermero = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();
        $informe = tap((new Informe(['titulo' => 'Informe', 'tipo' => 'enfermeria', 'contenido' => 'Texto']))->forceFill(['user_id' => $enfermero->id]))->save();

        $this->actingAs($enfermero)->get(route('residentes.historial', $residente))->assertOk();
        $this->actingAs($enfermero)->get(route('medicacion.index', $residente->id))->assertOk();
        $this->actingAs($enfermero)->get(route('informes.show', $informe))->assertOk();

        $consultas = Log::where('action', 'viewed')->where('user_id', $enfermero->id)->get();
        $this->assertEqualsCanonicalizing(['historial', 'medicacion', 'informe'], $consultas->pluck('changes.pantalla')->all());
        $this->assertSame($residente->id, $consultas->firstWhere('changes.pantalla', 'historial')->loggable_id);
    }

    public function test_denied_access_is_not_logged_as_a_consultation(): void
    {
        $cocinero = User::factory()->role(Role::Cocinero)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($cocinero)->get(route('medicacion.index', $residente->id))->assertForbidden();

        $this->assertSame(0, Log::where('action', 'viewed')->count());
    }

    public function test_audit_screen_shows_annulments_with_their_reason(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $this->actingAs(User::factory()->role(Role::Enfermero)->create());
        Diagnostico::create(['residente_id' => Residente::factory()->create()->id, 'nombre' => 'Asma', 'tipo' => 'cronico', 'fecha_diagnostico' => now()])
            ->anular('Registrado en el residente equivocado');

        $this->actingAs($director)->get(route('logs.index', ['action' => 'annulled']))
            ->assertOk()
            ->assertSee('Anulado')
            ->assertSee('Motivo: Registrado en el residente equivocado')
            ->assertDontSee('annulled</span>', false);
    }
}
