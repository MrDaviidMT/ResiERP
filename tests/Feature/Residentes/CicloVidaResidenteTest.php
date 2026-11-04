<?php

namespace Tests\Feature\Residentes;

use App\Enums\Role;
use App\Models\Habitacion;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// D10 y D12 (auditoría): una cama no puede quedar ocupada por alguien que ya no está en el centro
class CicloVidaResidenteTest extends TestCase
{
    use RefreshDatabase;

    private function datos(Residente $residente, array $extra = []): array
    {
        return array_merge([
            'nombre' => $residente->nombre, 'apellidos' => $residente->apellidos, 'dni' => $residente->dni,
            'nacimiento' => $residente->nacimiento->format('Y-m-d'), 'ingreso' => $residente->ingreso->format('Y-m-d'),
            'habitacion_id' => $residente->habitacion_id,
        ], $extra);
    }

    // D12: registrar la defunción libera la cama y archiva al residente
    public function test_recording_a_death_frees_the_bed_and_archives_the_resident(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $habitacion = Habitacion::factory()->create(['tipo' => 'individual', 'estado' => 'ocupada']);
        $residente = Residente::factory()->create(['habitacion_id' => $habitacion->id]);

        $this->actingAs($director)
            ->put(route('residentes.update', $residente->id), $this->datos($residente, ['fecha_defuncion' => now()->toDateString()]))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($residente);
        $this->assertNull($residente->fresh()->habitacion_id);
        $this->assertSame('libre', $habitacion->fresh()->estado);
        $this->assertNotNull(Residente::withTrashed()->find($residente->id)->fecha_defuncion);
    }

    // Registrar una defunción es dar de baja: exige el permiso de baja, no solo el de editar
    public function test_recording_a_death_requires_the_discharge_permission(): void
    {
        $ts = User::factory()->role(Role::TrabajadorSocial)->create();
        $residente = Residente::factory()->create(['habitacion_id' => Habitacion::factory()->create()->id]);

        $this->actingAs($ts)
            ->put(route('residentes.update', $residente->id), $this->datos($residente, ['fecha_defuncion' => now()->toDateString()]))
            ->assertSessionHasErrors('fecha_defuncion');

        $this->assertNotSoftDeleted($residente);
    }

    // D10: un residente archivado no se edita ni puede ocupar una cama
    public function test_an_archived_resident_cannot_be_edited_or_given_a_room(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $habitacion = Habitacion::factory()->create(['tipo' => 'individual']);
        $residente = Residente::factory()->create();
        $this->actingAs($director)->delete(route('residentes.destroy', $residente->id));
        $this->assertSoftDeleted($residente);

        $this->actingAs($director)->get(route('residentes.edit', $residente->id))->assertNotFound();
        $this->actingAs($director)
            ->put(route('residentes.update', $residente->id), $this->datos($residente, ['habitacion_id' => $habitacion->id]))
            ->assertNotFound();

        $this->assertNull(Residente::withTrashed()->find($residente->id)->habitacion_id);
        $this->assertSame(0, $habitacion->fresh()->residentes()->count());
    }

    // Consecuencia de D10: la plaza liberada se puede volver a asignar sin duplicar ocupación
    public function test_a_freed_individual_room_can_host_a_new_resident(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $habitacion = Habitacion::factory()->create(['tipo' => 'individual']);
        $primero = Residente::factory()->create(['habitacion_id' => $habitacion->id]);
        $this->actingAs($director)->delete(route('residentes.destroy', $primero->id));

        $this->actingAs($director)->post(route('residentes.store'), [
            'nombre' => 'Nueva', 'apellidos' => 'Residente', 'dni' => '99999999R',
            'nacimiento' => '1940-05-05', 'ingreso' => now()->toDateString(), 'habitacion_id' => $habitacion->id,
        ])->assertSessionHas('success');

        $this->assertSame(1, $habitacion->fresh()->residentes()->count());
    }
}
