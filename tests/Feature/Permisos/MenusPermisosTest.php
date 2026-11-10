<?php

namespace Tests\Feature\Permisos;

use App\Enums\Role;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// El menú del día lo gestiona cocina; el resto del personal no lo puede cambiar
class MenusPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_staff_can_create_a_menu(): void
    {
        $this->actingAs(User::factory()->role(Role::Cocinero)->create())
            ->post(route('menus.store'), ['fecha' => '2026-12-01', 'comida' => 'Lentejas'])
            ->assertRedirect(route('menus.index'));

        $this->assertSame('Lentejas', Menu::sole()->comida);
    }

    public function test_staff_without_permission_cannot_create_a_menu(): void
    {
        $this->actingAs(User::factory()->role(Role::Auxiliar)->create())
            ->post(route('menus.store'), ['fecha' => '2026-12-01', 'comida' => 'Lentejas'])
            ->assertForbidden();

        $this->assertSame(0, Menu::count());
    }
}
