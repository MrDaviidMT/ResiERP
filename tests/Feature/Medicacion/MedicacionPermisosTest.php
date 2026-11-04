<?php

namespace Tests\Feature\Medicacion;

use App\Enums\Role;
use App\Models\Medicacion;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Auxiliares ven la pauta, solo enfermería la modifica
class MedicacionPermisosTest extends TestCase
{
    use RefreshDatabase;

    private function pauta(): array
    {
        return ['medicamento' => 'Paracetamol', 'dosis' => '1 g'];
    }

    public function test_auxiliary_sees_the_schedule_but_not_the_prescription_form(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();
        $residente = Residente::factory()->create();
        Medicacion::create(['residente_id' => $residente->id, 'medicamento' => 'Omeprazol', 'dosis' => '20 mg']);

        $this->actingAs($auxiliar)->get(route('medicacion.index', $residente->id))
            ->assertOk()
            ->assertSee('Omeprazol')
            ->assertDontSee('Recetar Fármaco')
            ->assertDontSee('Retirar medicamento');

        $this->actingAs($auxiliar)->post(route('medicacion.store', $residente->id), $this->pauta())->assertForbidden();
    }

    public function test_director_can_view_but_not_prescribe(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($director)->get(route('medicacion.index', $residente->id))->assertOk();
        $this->actingAs($director)->post(route('medicacion.store', $residente->id), $this->pauta())->assertForbidden();

        $this->assertSame(0, Medicacion::count());
    }

    public function test_nurse_can_prescribe(): void
    {
        $enfermero = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($enfermero)->get(route('medicacion.index', $residente->id))->assertSee('Recetar Fármaco');
        $this->actingAs($enfermero)->post(route('medicacion.store', $residente->id), $this->pauta())->assertSessionHas('success');

        $this->assertSame(1, Medicacion::count());
    }
}
