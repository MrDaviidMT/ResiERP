<?php

namespace Tests\Feature\Fichajes;

use App\Enums\Role;
use App\Models\Fichaje;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// ET art. 34.9. El registro debe poder entregarse al trabajador,
// a sus representantes y a la Inspección de Trabajo
class ExportarJornadaTest extends TestCase
{
    use RefreshDatabase;

    private function fichaje(User $user, string $entrada = '2026-10-20 08:00:00', ?string $salida = '2026-10-20 15:00:00'): Fichaje
    {
        return Fichaje::create(['user_id' => $user->id, 'entrada' => $entrada, 'salida' => $salida]);
    }

    public function test_an_employee_can_export_their_own_record(): void
    {
        $trabajadora = User::factory()->role(Role::Auxiliar)->create(['name' => 'Lucía Soto']);
        $otra = User::factory()->role(Role::Auxiliar)->create(['name' => 'Marta Ruiz']);
        $this->fichaje($trabajadora);
        $this->fichaje($otra);

        $csv = $this->actingAs($trabajadora)->get(route('fichajes.exportar'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('Lucía Soto', $csv);
        $this->assertStringNotContainsString('Marta Ruiz', $csv, 'El trabajador no debe ver fichajes ajenos');
        $this->assertStringContainsString('20/10/2026 08:00', $csv);
        $this->assertStringContainsString('7,00', $csv, 'Debe constar el total de horas');
    }

    public function test_the_export_includes_corrections_and_annulments(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $trabajadora = User::factory()->role(Role::Auxiliar)->create();
        $corregido = $this->fichaje($trabajadora, '2026-10-20 08:00:00', null);
        $anulado = $this->fichaje($trabajadora, '2026-10-21 08:00:00');

        $this->actingAs($director)->put(route('fichajes.update', $corregido), [
            'entrada_fecha' => '2026-10-20', 'entrada_hora' => '08:00', 'salida_fecha' => '2026-10-20', 'salida_hora' => '15:00',
            'motivo' => 'Olvidó registrar la salida',
        ]);
        $this->actingAs($director)->post(route('fichajes.anular', $anulado), ['motivo' => 'Fichaje duplicado por error']);

        $csv = $this->actingAs($director)->get(route('fichajes.exportar'))->streamedContent();

        $this->assertStringContainsString('Olvidó registrar la salida', $csv);
        $this->assertStringContainsString('Anulado', $csv);
        $this->assertStringContainsString('Fichaje duplicado por error', $csv);
    }

    public function test_the_export_respects_the_filters_and_is_audited(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $trabajadora = User::factory()->role(Role::Auxiliar)->create(['name' => 'Lucía Soto']);
        $this->fichaje($trabajadora, '2026-09-01 08:00:00', '2026-09-01 15:00:00');
        $this->fichaje($trabajadora, '2026-10-20 08:00:00', '2026-10-20 15:00:00');

        $csv = $this->actingAs($director)->get(route('fichajes.exportar', ['desde' => '2026-10-01']))->streamedContent();

        $this->assertStringContainsString('20/10/2026', $csv);
        $this->assertStringNotContainsString('01/09/2026', $csv);
        $this->assertSame('exported', Log::where('action', 'exported')->sole()->action);
    }
}
