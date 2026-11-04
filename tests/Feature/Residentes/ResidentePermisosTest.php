<?php

namespace Tests\Feature\Residentes;

use App\Enums\Role;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Alta y edición, dirección y trabajo social; baja, solo dirección
class ResidentePermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_worker_can_edit_but_not_discharge(): void
    {
        $ts = User::factory()->role(Role::TrabajadorSocial)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($ts)->get(route('residentes.edit', $residente))->assertOk();
        $this->actingAs($ts)->delete(route('residentes.destroy', $residente->id))->assertForbidden();

        $this->assertNotSoftDeleted($residente);
    }

    public function test_nurse_cannot_admit_or_edit_residents(): void
    {
        $enfermero = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($enfermero)->get(route('residentes.create'))->assertForbidden();
        $this->actingAs($enfermero)->get(route('residentes.edit', $residente))->assertForbidden();
        $this->actingAs($enfermero)->get(route('residentes.show', $residente))->assertOk();
    }
}
