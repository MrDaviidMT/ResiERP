<?php

namespace Tests\Feature\Permisos;

use App\Enums\Permission as P;
use App\Enums\Role as R;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_gets_the_role_template(): void
    {
        $user = User::factory()->role(R::Auxiliar)->create();

        $this->assertSame(R::Auxiliar, $user->rol());
        $this->assertTrue($user->can(P::CuidadosRegistrar));
        $this->assertFalse($user->can(P::MedicacionEditar));
    }

    public function test_superadmin_has_every_permission(): void
    {
        $user = User::factory()->role(R::Superadmin)->create();

        foreach (P::cases() as $permiso) {
            $this->assertTrue($user->can($permiso), $permiso->value);
        }
    }

    public function test_director_can_change_an_employee_role(): void
    {
        $director = User::factory()->role(R::Director)->create();
        $empleado = User::factory()->role(R::Auxiliar)->create();

        $this->actingAs($director)->put(route('users.update', $empleado), [
            'name' => $empleado->name,
            'email' => $empleado->email,
            'role' => R::Enfermero->value,
        ])->assertSessionHas('success');

        $this->assertSame([R::Enfermero->value], $empleado->fresh()->getRoleNames()->all());
    }

    public function test_director_cannot_grant_superadmin(): void
    {
        $director = User::factory()->role(R::Director)->create();
        $empleado = User::factory()->role(R::Auxiliar)->create();

        $this->actingAs($director)->put(route('users.update', $empleado), [
            'name' => $empleado->name,
            'email' => $empleado->email,
            'role' => R::Superadmin->value,
        ])->assertSessionHasErrors('role');

        $this->assertFalse($empleado->fresh()->esSuperadmin());
    }
}
