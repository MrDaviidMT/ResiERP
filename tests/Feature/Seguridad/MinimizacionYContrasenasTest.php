<?php

namespace Tests\Feature\Seguridad;

use App\Enums\Role;
use App\Models\Familiar;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Minimización de datos identificativos y contraseñas robustas
class MinimizacionYContrasenasTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_does_not_see_the_resident_document(): void
    {
        $residente = Residente::factory()->create();

        $this->actingAs(User::factory()->role(Role::Cocinero)->create())
            ->get(route('residentes.index'))->assertOk()->assertDontSee($residente->dni);

        $this->actingAs(User::factory()->role(Role::Recepcionista)->create())
            ->get(route('residentes.index'))->assertSee($residente->dni);
    }

    public function test_auxiliaries_see_relatives_without_their_contact_details(): void
    {
        $residente = Residente::factory()->create();
        $familiar = Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600111222', 'email' => 'ana@example.test']);
        $familiar->residentes()->attach($residente->id, ['parentesco' => 'hija', 'autorizado' => true]);

        $this->actingAs(User::factory()->role(Role::Auxiliar)->create())
            ->get(route('familiares.lista'))
            ->assertSee('Ana Pérez')
            ->assertDontSee('12345678Z')
            ->assertDontSee('600111222');

        $this->actingAs(User::factory()->role(Role::Recepcionista)->create())
            ->get(route('familiares.lista'))->assertSee('600111222');
    }

    public function test_new_employee_passwords_must_be_strong(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $datos = ['name' => 'Nueva', 'email' => 'nueva@example.test', 'role' => Role::Auxiliar->value];

        $this->actingAs($director)->post(route('users.store'), $datos + ['password' => 'secreta-2026', 'password_confirmation' => 'secreta-2026'])
            ->assertSessionHasErrors('password');
        $this->actingAs($director)->post(route('users.store'), $datos + ['password' => 'solo-letras-sin-numeros', 'password_confirmation' => 'solo-letras-sin-numeros'])
            ->assertSessionHasErrors('password');

        $this->assertSame(0, User::where('email', 'nueva@example.test')->count());

        $this->actingAs($director)->post(route('users.store'), $datos + ['password' => 'clave-muy-larga-2026', 'password_confirmation' => 'clave-muy-larga-2026'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, User::where('email', 'nueva@example.test')->count());
    }
}
