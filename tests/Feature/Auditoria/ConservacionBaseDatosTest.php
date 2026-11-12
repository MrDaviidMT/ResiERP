<?php

namespace Tests\Feature\Auditoria;

use App\Enums\Role;
use App\Models\Diagnostico;
use App\Models\Fichaje;
use App\Models\Habitacion;
use App\Models\Incidencia;
use App\Models\Log;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Aunque se salte la aplicación, la base de datos no borra historial en cascada
class ConservacionBaseDatosTest extends TestCase
{
    use RefreshDatabase;

    private function assertBorradoBloqueado(string $tabla, int $id): void
    {
        try {
            DB::table($tabla)->where('id', $id)->delete();
            $this->fail("La base de datos permitió borrar {$tabla} #{$id} con historial");
        } catch (QueryException) {
            $this->assertSame(1, DB::table($tabla)->where('id', $id)->count());
        }
    }

    public function test_a_resident_with_clinical_records_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->role(Role::Enfermero)->create());
        $residente = Residente::factory()->create();
        Diagnostico::create(['residente_id' => $residente->id, 'nombre' => 'Asma', 'tipo' => 'cronico', 'fecha_diagnostico' => now()]);

        $this->assertBorradoBloqueado('residentes', $residente->id);
        $this->assertSame(1, Diagnostico::count());
    }

    public function test_a_user_with_time_entries_or_audit_trail_cannot_be_deleted(): void
    {
        $empleado = User::factory()->create();
        Fichaje::create(['user_id' => $empleado->id, 'entrada' => now()->subHours(8), 'salida' => now()]);

        $this->assertBorradoBloqueado('users', $empleado->id);

        $autor = User::factory()->create();
        Log::create(['user_id' => $autor->id, 'loggable_type' => User::class, 'loggable_id' => $autor->id, 'action' => 'updated']);
        $this->assertBorradoBloqueado('users', $autor->id);
    }

    // Declarado en el esquema, no heredado del comportamiento por defecto de cada motor
    public function test_author_foreign_keys_restrict_deletion_explicitly(): void
    {
        foreach (['actividades', 'cuidados_diarios', 'informes'] as $tabla) {
            $clave = collect(Schema::getForeignKeys($tabla))->firstWhere('columns', ['user_id']);
            $this->assertSame('restrict', strtolower($clave['on_delete']), "{$tabla}.user_id");
        }
    }

    public function test_a_room_with_incident_history_cannot_be_deleted(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $habitacion = Habitacion::factory()->create();
        Incidencia::create(['categoria' => 'limpieza', 'titulo' => 'Suelo mojado', 'prioridad' => 'leve', 'habitacion_id' => $habitacion->id]);

        $this->assertBorradoBloqueado('habitaciones', $habitacion->id);

        // Desde la aplicación: mensaje claro en lugar de un error del servidor
        $this->actingAs($director)->delete(route('habitaciones.destroy', $habitacion->id))->assertSessionHasErrors('error');
        $this->assertNotNull($habitacion->fresh());
    }

    public function test_a_room_without_history_can_still_be_deleted(): void
    {
        $habitacion = Habitacion::factory()->create();

        $this->actingAs(User::factory()->role(Role::Director)->create())
            ->delete(route('habitaciones.destroy', $habitacion->id))->assertRedirect(route('habitaciones.index'));

        $this->assertNull($habitacion->fresh());
    }
}
