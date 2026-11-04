<?php

namespace Tests\Feature\Residentes;

use App\Enums\Role;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// L1 (auditoría): el grupo sanguíneo y el grado de dependencia son datos de salud (Ley 41/2002 art. 16)
class DatosSaludFichaTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_and_reception_do_not_see_health_data(): void
    {
        $residente = Residente::factory()->create(['grupo_sanguineo' => 'AB-', 'dependencia' => 'gran_dependiente']);

        foreach ([Role::Cocinero, Role::Recepcionista] as $rol) {
            $this->actingAs(User::factory()->role($rol)->create())
                ->get(route('residentes.show', $residente))
                ->assertOk()
                ->assertDontSee('AB-')
                ->assertDontSee('Gran dependiente');
        }
    }

    public function test_care_staff_see_health_data(): void
    {
        $residente = Residente::factory()->create(['grupo_sanguineo' => 'AB-', 'dependencia' => 'gran_dependiente']);

        $this->actingAs(User::factory()->role(Role::Auxiliar)->create())
            ->get(route('residentes.show', $residente))
            ->assertSee('AB-')
            ->assertSee('Gran dependiente');
    }

    public function test_blood_type_filter_is_ignored_without_permission(): void
    {
        Residente::factory()->create(['nombre' => 'Carmen', 'grupo_sanguineo' => 'AB-']);
        Residente::factory()->create(['nombre' => 'Lucía', 'grupo_sanguineo' => 'O+']);

        $this->actingAs(User::factory()->role(Role::Cocinero)->create())
            ->get(route('residentes.index', ['grupo_sanguineo' => 'AB-']))
            ->assertSee('Carmen')
            ->assertSee('Lucía');
    }
}
