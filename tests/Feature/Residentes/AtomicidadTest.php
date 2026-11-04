<?php

namespace Tests\Feature\Residentes;

use App\Enums\Role;
use App\Models\Familiar;
use App\Models\Habitacion;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

// Operaciones de varios pasos atómicas y con bloqueo de fila
class AtomicidadTest extends TestCase
{
    use RefreshDatabase;

    // Si falla la vinculación, no queda un familiar suelto sin residentes
    public function test_creating_a_relative_and_its_links_is_atomic(): void
    {
        $ts = User::factory()->role(Role::TrabajadorSocial)->create();
        $residente = Residente::factory()->create();

        // Simula un fallo justo al insertar en la tabla intermedia
        DB::listen(function ($consulta) {
            if (str_contains($consulta->sql, 'insert into "familiar_residente"') || str_contains($consulta->sql, 'insert into `familiar_residente`')) {
                throw new RuntimeException('fallo simulado al vincular');
            }
        });

        try {
            $this->actingAs($ts)->post(route('familiares.storeGeneral'), [
                'nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000',
                'vinculaciones' => [['residente_id' => $residente->id, 'parentesco' => 'Hija', 'autorizado' => '1', 'es_emergencia' => '0']],
            ]);
        } catch (RuntimeException) {
        }

        $this->assertSame(0, Familiar::count(), 'El familiar quedó creado sin sus vinculaciones');
    }

    // Cambiar el tipo o el mantenimiento de una habitación bloquea su fila
    public function test_room_changes_lock_the_row(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite no implementa SELECT ... FOR UPDATE; se comprueba en MySQL.');
        }

        $director = User::factory()->role(Role::Director)->create();
        $habitacion = Habitacion::factory()->create(['tipo' => 'doble']);
        $consultas = [];
        // Closure con referencia: una función flecha captura la variable por valor y no acumularía nada
        DB::listen(function ($consulta) use (&$consultas) {
            $consultas[] = $consulta->sql;
        });

        $this->actingAs($director)->put(route('habitaciones.update', $habitacion), ['tipo' => 'individual']);

        $this->assertTrue(collect($consultas)->contains(fn (string $sql) => str_contains($sql, 'habitaciones') && str_contains($sql, 'for update')));
    }

    // La regla observable que protege el bloqueo: no se puede reducir la capacidad por debajo de los ocupantes
    public function test_a_double_room_with_two_residents_cannot_become_single(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $habitacion = Habitacion::factory()->create(['tipo' => 'doble']);
        Residente::factory()->count(2)->create(['habitacion_id' => $habitacion->id]);

        $this->actingAs($director)->put(route('habitaciones.update', $habitacion), ['tipo' => 'individual'])
            ->assertSessionHasErrors('error');

        $this->assertSame('doble', $habitacion->fresh()->tipo);
    }
}
