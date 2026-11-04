<?php

namespace Tests\Feature\Permisos;

use App\Enums\Permission as P;
use App\Enums\Role as R;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AntiEscaladaTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_assign_clinical_roles_without_clinical_permissions(): void
    {
        $director = User::factory()->role(R::Director)->create();

        $this->assertFalse($director->can(P::MedicacionEditar));
        $this->assertTrue($director->can('asignarRol', [User::class, R::Enfermero]));
    }

    public function test_cannot_assign_a_role_with_administrative_permissions_you_lack(): void
    {
        // Un coordinador hipotético que gestiona empleados pero no ve la auditoría
        $coordinador = User::factory()->role(R::Auxiliar)->create();
        $coordinador->givePermissionTo(P::EmpleadosAsignarRoles, P::EmpleadosEditar);

        $this->assertTrue($coordinador->can('asignarRol', [User::class, R::Cocinero]));
        $this->assertFalse($coordinador->can('asignarRol', [User::class, R::Director]));
    }

    public function test_nobody_changes_their_own_role(): void
    {
        $director = User::factory()->role(R::Director)->create();

        $this->actingAs($director)->put(route('users.update', $director), [
            'name' => $director->name, 'email' => $director->email, 'role' => R::Enfermero->value,
        ])->assertSessionHasErrors('role');

        $this->assertSame(R::Director, $director->fresh()->rol());
    }

    public function test_superadmin_is_hidden_from_the_employee_list_and_profile(): void
    {
        $director = User::factory()->role(R::Director)->create();
        $admin = User::factory()->role(R::Superadmin)->create(['name' => 'Cuenta técnica']);

        $this->actingAs($director)->get(route('users.index'))->assertDontSee('Cuenta técnica');
        $this->actingAs($director)->get(route('users.show', $admin))->assertNotFound();
        $this->actingAs($director)->get(route('users.edit', $admin))->assertNotFound();
    }

    public function test_superadmin_role_is_not_offered_or_accepted(): void
    {
        $admin = User::factory()->role(R::Superadmin)->create();

        $this->actingAs($admin)->get(route('users.create'))->assertDontSee('value="superadmin"', false);
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Otra', 'email' => 'otra@example.test', 'password' => 'clave-muy-larga-2026', 'password_confirmation' => 'clave-muy-larga-2026',
            'role' => R::Superadmin->value,
        ])->assertSessionHasErrors('role');

        $this->assertSame(1, User::role(R::Superadmin)->count());
    }

    public function test_administrative_permissions_are_explicit(): void
    {
        // Anular registros no clínicos sí da poder (anti-escalada); anular o retirar registros clínicos no
        foreach ([P::EmpleadosAsignarRoles, P::AuditoriaVer, P::FichajesEditar, P::InformesAnular, P::IncidenciasAnular, P::VisitasAnular, P::FichajesAnular] as $permiso) {
            $this->assertTrue($permiso->esAdministrativo(), $permiso->value);
        }
        foreach ([P::DiagnosticosAnular, P::MedicacionRetirar, P::MedicacionAnular, P::ResidentesVer, P::InformesCrearGeneral] as $permiso) {
            $this->assertFalse($permiso->esAdministrativo(), $permiso->value);
        }
    }
}
