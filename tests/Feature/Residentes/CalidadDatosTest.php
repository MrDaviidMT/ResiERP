<?php

namespace Tests\Feature\Residentes;

use App\Enums\Role;
use App\Models\ConstanteVital;
use App\Models\Diagnostico;
use App\Models\Familiar;
use App\Models\Habitacion;
use App\Models\Incidencia;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// No entran datos imposibles ni documentos inventados
class CalidadDatosTest extends TestCase
{
    use RefreshDatabase;

    private function alta(array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Carmen', 'apellidos' => 'Gil', 'dni' => '12345678Z',
            'nacimiento' => '1940-03-02', 'ingreso' => '2026-01-10',
            'habitacion_id' => Habitacion::factory()->create()->id,
        ], $extra);
    }

    public function test_a_resident_cannot_be_admitted_with_an_invalid_document(): void
    {
        $this->actingAs(User::factory()->role(Role::Director)->create())
            ->post(route('residentes.store'), $this->alta(['dni' => '12345678A']))
            ->assertSessionHasErrors('dni');

        $this->assertSame(0, Residente::count());
    }

    public function test_the_document_is_normalised_so_duplicates_are_detected(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $this->actingAs($director)->post(route('residentes.store'), $this->alta(['dni' => '12.345.678-z']));
        $this->assertSame('12345678Z', Residente::sole()->dni);

        // El mismo documento escrito de otra forma ya no cuela como residente nuevo
        $this->actingAs($director)->post(route('residentes.store'), $this->alta(['dni' => '12345678Z', 'nombre' => 'Otra']))
            ->assertSessionHasErrors('dni');
        $this->assertSame(1, Residente::count());
    }

    public function test_a_relative_cannot_be_created_with_an_invalid_document(): void
    {
        $residente = Residente::factory()->create();

        $this->actingAs(User::factory()->role(Role::TrabajadorSocial)->create())
            ->post(route('familiares.storeGeneral'), [
                'nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => 'INVENTADO', 'telefono' => '600000000',
                'vinculaciones' => [['residente_id' => $residente->id, 'parentesco' => 'Hija', 'autorizado' => '1', 'es_emergencia' => '0']],
            ])->assertSessionHasErrors('dni');

        $this->assertSame(0, Familiar::count());
    }

    public function test_blood_group_must_be_one_of_the_known_values(): void
    {
        $this->actingAs(User::factory()->role(Role::Director)->create())
            ->post(route('residentes.store'), $this->alta(['grupo_sanguineo' => 'XY+']))
            ->assertSessionHasErrors('grupo_sanguineo');
    }

    public function test_clinical_records_cannot_be_dated_in_the_future(): void
    {
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();
        $manana = now()->addDay()->toDateString();

        $this->actingAs($enfermera)->post(route('diagnosticos.store', $residente), [
            'nombre' => 'Asma', 'tipo' => 'cronico', 'fecha_diagnostico' => $manana,
        ])->assertSessionHasErrors('fecha_diagnostico');

        $this->actingAs($enfermera)->post(route('constantes.store', $residente), [
            'temperatura' => 36.5, 'fecha_registro' => now()->addDay()->toDateTimeString(),
        ])->assertSessionHasErrors('fecha_registro');

        $this->assertSame(0, Diagnostico::count());
        $this->assertSame(0, ConstanteVital::count());
    }

    public function test_systolic_pressure_must_be_higher_than_diastolic(): void
    {
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($enfermera)->post(route('constantes.store', $residente), [
            'tension_sistolica' => 70, 'tension_diastolica' => 120, 'fecha_registro' => now()->toDateTimeString(),
        ])->assertSessionHasErrors('tension_sistolica');

        $this->assertSame(0, ConstanteVital::count());
    }

    public function test_an_archived_resident_cannot_be_linked_to_new_records(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $residente = Residente::factory()->create();
        $this->actingAs($director)->delete(route('residentes.destroy', $residente->id));

        $this->actingAs($director)->post(route('incidencias.store'), [
            'categoria' => 'sanitaria', 'titulo' => 'Caída', 'prioridad' => 'grave', 'residente_id' => $residente->id,
        ])->assertSessionHasErrors('residente_id');

        $this->assertSame(0, Incidencia::count());
    }
}
