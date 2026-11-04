<?php

namespace Tests\Feature\Auditoria;

use App\Models\Habitacion;
use App\Models\Log;
use App\Models\Menu;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_does_not_store_the_hash_in_the_log(): void
    {
        $admin = User::factory()->role('superadmin')->create();
        $empleado = User::factory()->create();

        $this->actingAs($admin);
        $empleado->update(['name' => 'Nombre nuevo', 'password' => 'otra-contraseña-segura']);

        $log = Log::where('action', 'updated')->where('loggable_id', $empleado->id)->sole();

        $this->assertArrayNotHasKey('password', $log->changes['old']);
        $this->assertArrayNotHasKey('password', $log->changes['new']);
        $this->assertSame('Nombre nuevo', $log->changes['new']['name']);
    }

    public function test_log_stores_ip_address_and_user_agent(): void
    {
        $admin = User::factory()->role('superadmin')->create();
        $empleado = User::factory()->create();

        $this->actingAs($admin)
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.7'])
            ->withHeader('User-Agent', 'NavegadorDePrueba/1.0')
            ->put(route('users.update', $empleado), ['name' => 'Nuevo nombre', 'email' => $empleado->email, 'role' => 'auxiliar']);

        $log = Log::where('action', 'updated')->where('loggable_id', $empleado->id)->sole();
        $this->assertSame('10.0.0.7', $log->ip_address);
        $this->assertSame('NavegadorDePrueba/1.0', $log->user_agent);
    }

    // L2 (auditoría): minimización de datos en el registro de auditoría
    public function test_update_log_only_contains_the_changed_fields(): void
    {
        $this->actingAs(User::factory()->role('superadmin')->create());
        $residente = Residente::factory()->create(['dni' => '11111111H', 'grupo_sanguineo' => 'AB-']);
        $habitacion = Habitacion::factory()->create();

        $residente->update(['habitacion_id' => $habitacion->id]);

        $log = Log::where('action', 'updated')->where('loggable_type', Residente::class)->sole();
        $this->assertSame(['habitacion_id'], array_keys($log->changes['old']));
        $this->assertSame(['habitacion_id'], array_keys($log->changes['new']));
        $this->assertNull($log->changes['old']['habitacion_id']);
        $this->assertSame($habitacion->id, $log->changes['new']['habitacion_id']);
    }

    public function test_create_log_does_not_copy_the_record(): void
    {
        $this->actingAs(User::factory()->role('superadmin')->create());

        $residente = Residente::factory()->create(['dni' => '11111111H']);

        $log = Log::where('action', 'created')->where('loggable_id', $residente->id)->where('loggable_type', Residente::class)->sole();
        $this->assertNull($log->changes);
    }

    public function test_delete_log_keeps_a_snapshot_for_traceability(): void
    {
        $this->actingAs(User::factory()->role('superadmin')->create());
        $menu = Menu::create(['fecha' => now()->toDateString(), 'comida' => 'Lentejas']);

        $menu->delete();

        $log = Log::where('action', 'deleted')->where('loggable_type', Menu::class)->sole();
        $this->assertSame('Lentejas', $log->changes['comida']);
    }

    public function test_audit_entries_cannot_be_modified(): void
    {
        $this->actingAs(User::factory()->role('superadmin')->create());
        User::factory()->create();
        $log = Log::where('action', 'created')->firstOrFail();

        try {
            $log->update(['action' => 'otra cosa']);
            $this->fail('Se pudo modificar un registro de auditoría');
        } catch (\LogicException) {
        }

        $this->assertSame('created', $log->fresh()->action);
        // Sí se puede borrar: lo necesita la política de conservación
        $log->delete();
        $this->assertNull(Log::find($log->id));
    }
}
