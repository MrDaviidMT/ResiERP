<?php

namespace Tests\Feature\Conservacion;

use App\Enums\Role;
use App\Models\Diagnostico;
use App\Models\Familiar;
use App\Models\Fichaje;
use App\Models\Log;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Política de conservación (RGPD art. 5.1.e). Ver config/conservacion.php
class PoliticaConservacionTest extends TestCase
{
    use RefreshDatabase;

    private function residenteVencido(): Residente
    {
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $this->actingAs($enfermera);

        $residente = Residente::factory()->create(['nombre' => 'Carmen', 'apellidos' => 'Gil', 'dni' => '12345678Z', 'grupo_sanguineo' => 'A+']);
        Diagnostico::create([
            'residente_id' => $residente->id, 'nombre' => 'Diabetes tipo 2', 'tipo' => 'cronico',
            'fecha_diagnostico' => now()->subYears(16), 'observaciones' => 'Vive con su hija Marta', 'medico' => 'Dr. Ruiz',
        ]);
        $familiar = Familiar::create(['nombre' => 'Marta', 'apellidos' => 'Gil', 'dni' => '11111111H', 'telefono' => '600000000']);
        $familiar->residentes()->attach($residente->id, ['parentesco' => 'hija', 'autorizado' => true]);

        $residente->update(['habitacion_id' => null]);
        $residente->delete();
        $residente->forceFill(['deleted_at' => now()->subYears(16)])->saveQuietly();

        return $residente;
    }

    public function test_dry_run_reports_without_changing_anything(): void
    {
        $residente = $this->residenteVencido();

        $this->artisan('conservacion:aplicar')
            ->expectsOutputToContain('Modo simulación')
            ->assertSuccessful();

        $this->assertSame('Carmen', Residente::withTrashed()->find($residente->id)->nombre);
        $this->assertSame('retention_dry_run', Log::where('action', 'retention_dry_run')->sole()->action);
        $this->assertSame(1, Log::where('action', 'retention_dry_run')->sole()->changes['resultado']['residentes_anonimizados']);
    }

    public function test_applying_it_anonymises_an_expired_resident(): void
    {
        $residente = $this->residenteVencido();

        $this->artisan('conservacion:aplicar --aplicar')->assertSuccessful();

        $anonimizado = Residente::withTrashed()->find($residente->id);
        $this->assertSame('ANON-'.$residente->id, $anonimizado->dni);
        $this->assertStringContainsString('anonimizado', $anonimizado->apellidos);
        $this->assertNull($anonimizado->grupo_sanguineo);
        $this->assertNotNull($anonimizado->anonimizado_at);

        // Lo clínico se conserva, pero sin textos libres ni nombres
        $diagnostico = Diagnostico::conAnulados()->sole();
        $this->assertSame('Diabetes tipo 2', $diagnostico->nombre);
        $this->assertNull($diagnostico->observaciones);
        $this->assertNull($diagnostico->medico);
        $this->assertSame(0, $anonimizado->familiares()->count());
        $this->assertSame('anonymised', Log::where('action', 'anonymised')->first()->action);
    }

    public function test_it_does_not_touch_data_within_the_retention_period(): void
    {
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $this->actingAs($enfermera);
        $residente = Residente::factory()->create(['nombre' => 'Lucía']);
        $residente->delete();
        $residente->forceFill(['deleted_at' => now()->subYears(5)])->saveQuietly();
        $fichaje = Fichaje::create(['user_id' => $enfermera->id, 'entrada' => now()->subYears(3), 'salida' => now()->subYears(3)->addHours(7)]);

        $this->artisan('conservacion:aplicar --aplicar')->assertSuccessful();

        $this->assertSame('Lucía', Residente::withTrashed()->find($residente->id)->nombre);
        $this->assertNotNull($fichaje->fresh());
    }

    public function test_it_deletes_expired_time_entries_and_audit_trail(): void
    {
        $empleada = User::factory()->role(Role::Auxiliar)->create();
        $viejo = Fichaje::create(['user_id' => $empleada->id, 'entrada' => now()->subYears(5), 'salida' => now()->subYears(5)->addHours(7)]);
        $reciente = Fichaje::create(['user_id' => $empleada->id, 'entrada' => now()->subYear(), 'salida' => now()->subYear()->addHours(7)]);
        $logViejo = Log::create(['user_id' => $empleada->id, 'loggable_type' => User::class, 'loggable_id' => $empleada->id, 'action' => 'updated']);
        $logViejo->forceFill(['created_at' => now()->subYears(7)])->saveQuietly();

        $this->artisan('conservacion:aplicar --aplicar')->assertSuccessful();

        $this->assertNull(Fichaje::conAnulados()->find($viejo->id));
        $this->assertNotNull(Fichaje::find($reciente->id));
        $this->assertNull(Log::find($logViejo->id));
    }

    public function test_it_anonymises_long_deactivated_accounts_keeping_the_audit_coherent(): void
    {
        $empleada = User::factory()->role(Role::Auxiliar)->create(['name' => 'Lucía Soto', 'email' => 'lucia@residencia.test']);
        Log::create(['user_id' => $empleada->id, 'loggable_type' => User::class, 'loggable_id' => $empleada->id, 'action' => 'updated']);
        $empleada->forceFill(['activo' => false, 'updated_at' => now()->subYears(7)])->saveQuietly();

        $this->artisan('conservacion:aplicar --aplicar')->assertSuccessful();

        $anonimizada = User::find($empleada->id);
        $this->assertStringContainsString('anonimizado', $anonimizada->name);
        $this->assertStringNotContainsString('lucia@residencia.test', $anonimizada->email);
        // La cuenta sigue existiendo: la auditoría antigua no queda huérfana
        $this->assertSame($empleada->id, Log::where('action', 'updated')->first()->user_id);
    }

    public function test_it_deletes_relatives_without_links(): void
    {
        $suelto = Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000']);
        $suelto->forceFill(['updated_at' => now()->subYears(2)])->saveQuietly();
        $vinculado = Familiar::create(['nombre' => 'Luis', 'apellidos' => 'Gómez', 'dni' => '11111111H', 'telefono' => '600000001']);
        $vinculado->residentes()->attach(Residente::factory()->create()->id, ['parentesco' => 'hijo', 'autorizado' => true]);
        $vinculado->forceFill(['updated_at' => now()->subYears(2)])->saveQuietly();

        $this->artisan('conservacion:aplicar --aplicar')->assertSuccessful();

        $this->assertNull(Familiar::find($suelto->id));
        $this->assertNotNull(Familiar::find($vinculado->id));
    }

    public function test_the_audit_screen_shows_the_run_in_spanish(): void
    {
        $this->residenteVencido();
        $this->artisan('conservacion:aplicar');

        $this->actingAs(User::factory()->role(Role::Director)->create())
            ->get(route('logs.index', ['action' => 'retention_dry_run']))
            ->assertOk()
            ->assertSee('Conservación (simulación)')
            ->assertSee('Tarea del sistema')
            ->assertSee('residentes anonimizados: 1')
            ->assertDontSee('retention_dry_run</span>', false);
    }
}
