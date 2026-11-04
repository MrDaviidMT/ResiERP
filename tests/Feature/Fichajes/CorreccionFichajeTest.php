<?php

namespace Tests\Feature\Fichajes;

use App\Enums\Role;
use App\Models\Fichaje;
use App\Models\FichajeCorreccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

// Estatuto de los Trabajadores art. 34.9: registro de jornada fiable y trazable
class CorreccionFichajeTest extends TestCase
{
    use RefreshDatabase;

    private User $director;

    private User $trabajadora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->director = User::factory()->role(Role::Director)->create();
        $this->trabajadora = User::factory()->role(Role::Auxiliar)->create();
    }

    private function fichajeDe(User $user, ?string $salida = null): Fichaje
    {
        return Fichaje::create(['user_id' => $user->id, 'entrada' => '2026-10-29 08:00:00', 'salida' => $salida]);
    }

    private function correccion(string $motivo = 'Olvidó registrar la salida; según el cuadrante salió a las 15:00'): array
    {
        return ['entrada_fecha' => '2026-10-29', 'entrada_hora' => '08:00', 'salida_fecha' => '2026-10-29', 'salida_hora' => '15:00', 'motivo' => $motivo];
    }

    public function test_correction_keeps_previous_and_new_values_with_reason(): void
    {
        $fichaje = $this->fichajeDe($this->trabajadora);

        $this->actingAs($this->director)->put(route('fichajes.update', $fichaje), $this->correccion())->assertSessionHas('success');

        $this->assertSame('2026-10-29 15:00:00', $fichaje->fresh()->salida->format('Y-m-d H:i:s'));
        $correccion = FichajeCorreccion::sole();
        $this->assertNull($correccion->salida_anterior);
        $this->assertSame('2026-10-29 15:00', $correccion->salida_nueva->format('Y-m-d H:i'));
        $this->assertSame($this->director->id, $correccion->corregido_por);

        // La trabajadora ve la corrección en su fichaje
        $this->actingAs($this->trabajadora)->get(route('fichajes.show', $fichaje))
            ->assertSee('Historial de correcciones')
            ->assertSee('Olvidó registrar la salida');
    }

    public function test_a_reason_is_required(): void
    {
        $fichaje = $this->fichajeDe($this->trabajadora);

        $this->actingAs($this->director)->put(route('fichajes.update', $fichaje), $this->correccion('corto'))->assertSessionHasErrors('motivo');

        $this->assertNull($fichaje->fresh()->salida);
        $this->assertSame(0, FichajeCorreccion::count());
    }

    public function test_nobody_corrects_or_annuls_their_own_time_entries(): void
    {
        $propio = $this->fichajeDe($this->director);

        $this->actingAs($this->director)->put(route('fichajes.update', $propio), $this->correccion())->assertForbidden();
        $this->actingAs($this->director)->post(route('fichajes.anular', $propio), ['motivo' => 'Fichaje duplicado por error'])->assertForbidden();
        $this->actingAs($this->director)->post(route('fichajes.store'), [
            'user_id' => $this->director->id, 'entrada_fecha' => '2026-10-28', 'entrada_hora' => '08:00', 'motivo' => 'No pude fichar ayer por la mañana',
        ])->assertSessionHasErrors('user_id');

        $this->assertSame(0, FichajeCorreccion::count());
        $this->assertSame(1, Fichaje::count());
    }

    public function test_manual_entry_for_another_employee_is_recorded_as_a_correction(): void
    {
        $this->actingAs($this->director)->post(route('fichajes.store'), [
            'user_id' => $this->trabajadora->id, 'entrada_fecha' => '2026-10-28', 'entrada_hora' => '08:00',
            'salida_fecha' => '2026-10-28', 'salida_hora' => '15:00', 'motivo' => 'El terminal de fichaje estaba averiado',
        ])->assertSessionHas('success');

        $correccion = FichajeCorreccion::sole();
        $this->assertNull($correccion->entrada_anterior);
        $this->assertSame('El terminal de fichaje estaba averiado', $correccion->motivo);
        $this->assertSame($this->trabajadora->id, $correccion->fichaje->user_id);
    }

    public function test_time_entries_are_annulled_not_deleted(): void
    {
        $fichaje = $this->fichajeDe($this->trabajadora, '2026-10-29 15:00:00');

        $this->actingAs($this->director)->delete("/fichajes/{$fichaje->id}")->assertMethodNotAllowed();
        $this->actingAs($this->director)->post(route('fichajes.anular', $fichaje), ['motivo' => 'Fichaje duplicado por error'])->assertRedirect();

        $this->assertSame(0, Fichaje::count());
        $this->assertSame(1, Fichaje::conAnulados()->count());
    }

    public function test_closing_someone_elses_entry_requires_a_correction(): void
    {
        $fichaje = $this->fichajeDe($this->trabajadora);

        $this->actingAs($this->director)->post(route('fichajes.salida', $fichaje))->assertForbidden();

        $this->assertNull($fichaje->fresh()->salida);
    }

    public function test_an_annulled_open_entry_does_not_block_clocking_in(): void
    {
        $abierto = $this->fichajeDe($this->trabajadora);
        $this->actingAs($this->director)->post(route('fichajes.anular', $abierto), ['motivo' => 'Fichaje registrado por error']);

        $this->actingAs($this->trabajadora)->post(route('fichajes.store'), ['user_id' => $this->trabajadora->id])->assertSessionHas('success');
    }

    public function test_corrections_are_append_only(): void
    {
        $fichaje = $this->fichajeDe($this->trabajadora);
        $this->actingAs($this->director)->put(route('fichajes.update', $fichaje), $this->correccion());
        $correccion = FichajeCorreccion::sole();

        try {
            $correccion->update(['motivo' => 'Otro motivo distinto']);
            $this->fail('Se pudo modificar una corrección');
        } catch (LogicException) {
        }
        $this->expectException(LogicException::class);
        $correccion->delete();
    }
}
