<?php

namespace Tests\Feature\Auditoria;

use App\Enums\Permission as P;
use App\Enums\Role as R;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_change_is_audited_with_who_and_what(): void
    {
        $director = User::factory()->role(R::Director)->create();
        $empleado = User::factory()->role(R::Auxiliar)->create();

        $this->actingAs($director)->put(route('users.update', $empleado), [
            'name' => $empleado->name, 'email' => $empleado->email, 'role' => R::Enfermero->value,
        ]);

        $retirado = Log::where('action', 'role_detached')->where('loggable_id', $empleado->id)->sole();
        $asignado = Log::where('action', 'role_attached')->where('loggable_id', $empleado->id)->sole();
        $this->assertSame($director->id, $asignado->user_id);
        $this->assertSame(['auxiliar'], $retirado->changes['roles']);
        $this->assertSame(['enfermero'], $asignado->changes['roles']);

        $this->actingAs($director)->get(route('logs.index'))->assertSee('Rol asignado')->assertSee('enfermero');
    }

    public function test_saving_without_changing_the_role_does_not_add_role_entries(): void
    {
        $director = User::factory()->role(R::Director)->create();
        $empleado = User::factory()->role(R::Auxiliar)->create();

        $this->actingAs($director)->put(route('users.update', $empleado), [
            'name' => 'Nombre corregido', 'email' => $empleado->email, 'role' => R::Auxiliar->value,
        ]);

        $this->assertSame(0, Log::whereIn('action', ['role_attached', 'role_detached'])->count());
    }

    public function test_direct_permission_grant_is_audited(): void
    {
        $director = User::factory()->role(R::Director)->create();
        $empleado = User::factory()->role(R::Auxiliar)->create();

        $this->actingAs($director);
        $empleado->givePermissionTo(P::FichajesVerTodos);

        $this->assertSame(['fichajes.ver.todos'], Log::where('action', 'permission_attached')->sole()->changes['permisos']);
    }
}
