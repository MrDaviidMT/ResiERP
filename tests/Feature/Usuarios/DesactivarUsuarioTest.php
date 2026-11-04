<?php

namespace Tests\Feature\Usuarios;

use App\Models\Fichaje;
use App\Models\Informe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesactivarUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivating_keeps_the_user_and_its_records(): void
    {
        $director = User::factory()->role('director')->create();
        $empleado = User::factory()->role('enfermero')->create();
        Fichaje::create(['user_id' => $empleado->id, 'entrada' => now()->subHours(8), 'salida' => now()]);
        tap((new Informe(['titulo' => 'Informe', 'tipo' => 'enfermeria', 'contenido' => 'Texto']))->forceFill(['user_id' => $empleado->id]))->save();

        $this->actingAs($director)
            ->post(route('users.desactivar', $empleado))
            ->assertSessionHas('success');

        $this->assertFalse($empleado->fresh()->activo);
        $this->assertSame(1, Fichaje::where('user_id', $empleado->id)->count());
        $this->assertSame(1, Informe::where('user_id', $empleado->id)->count());
    }

    public function test_users_cannot_be_deleted(): void
    {
        $director = User::factory()->role('director')->create();
        $empleado = User::factory()->create();

        $this->actingAs($director)->delete("/users/{$empleado->id}")->assertMethodNotAllowed();

        $this->assertNotNull($empleado->fresh());
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $empleado = User::factory()->create();
        $empleado->forceFill(['activo' => false])->save();

        $this->post('/login', ['email' => $empleado->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_open_session_is_closed_after_deactivation(): void
    {
        $empleado = User::factory()->create();
        $this->actingAs($empleado)->get(route('dashboard'))->assertOk();

        $empleado->forceFill(['activo' => false])->save();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_user_cannot_deactivate_themselves(): void
    {
        $director = User::factory()->role('director')->create();

        $this->actingAs($director)
            ->post(route('users.desactivar', $director))
            ->assertSessionHas('error');

        $this->assertTrue($director->fresh()->activo);
    }

    public function test_superadmin_cannot_be_deactivated_by_the_director(): void
    {
        $director = User::factory()->role('director')->create();
        $admin = User::factory()->role('superadmin')->create();

        // Para quien no es superadministrador, esa cuenta "no existe"
        $this->actingAs($director)
            ->post(route('users.desactivar', $admin))
            ->assertNotFound();

        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_deactivated_user_can_be_reactivated(): void
    {
        $director = User::factory()->role('director')->create();
        $empleado = User::factory()->create();
        $empleado->forceFill(['activo' => false])->save();

        $this->actingAs($director)
            ->post(route('users.reactivar', $empleado))
            ->assertSessionHas('success');

        $this->assertTrue($empleado->fresh()->activo);
    }

    public function test_list_hides_deactivated_users_unless_filtered(): void
    {
        $director = User::factory()->role('director')->create();
        $activo = User::factory()->create(['name' => 'Empleada Activa']);
        $baja = User::factory()->create(['name' => 'Empleado De Baja']);
        $baja->forceFill(['activo' => false])->save();

        $this->actingAs($director)->get(route('users.index'))
            ->assertSee('Empleada Activa')
            ->assertDontSee('Empleado De Baja');

        $this->actingAs($director)->get(route('users.index', ['estado' => 'desactivados']))
            ->assertSee('Empleado De Baja')
            ->assertDontSee('Empleada Activa');
    }
}
