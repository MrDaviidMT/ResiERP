<?php

namespace Tests\Feature\Permisos;

use App\Enums\Role;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_nurses_and_auxiliaries_can_record_vital_signs_and_daily_care(): void
    {
        $residente = Residente::factory()->create();

        foreach ([Role::Enfermero, Role::Auxiliar] as $rol) {
            $this->actingAs(User::factory()->role($rol)->create())
                ->get(route('residentes.historial', $residente))
                ->assertOk()
                ->assertSee(route('constantes.store', $residente))
                ->assertSee(route('cuidados_diarios.store', $residente));
        }
    }
}
