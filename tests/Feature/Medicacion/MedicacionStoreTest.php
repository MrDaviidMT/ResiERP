<?php

namespace Tests\Feature\Medicacion;

use App\Models\Medicacion;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicacionStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_medication_is_added_to_an_existing_resident(): void
    {
        $user = User::factory()->role('enfermero')->create();
        $residente = Residente::factory()->create();

        $this->actingAs($user)
            ->post(route('medicacion.store', $residente->id), ['medicamento' => 'Paracetamol', 'dosis' => '1 g'])
            ->assertSessionHas('success');

        $this->assertSame($residente->id, Medicacion::sole()->residente_id);
    }

    public function test_unknown_resident_returns_not_found(): void
    {
        $user = User::factory()->role('enfermero')->create();

        $this->actingAs($user)
            ->post(route('medicacion.store', 999), ['medicamento' => 'Paracetamol', 'dosis' => '1 g'])
            ->assertNotFound();

        $this->assertSame(0, Medicacion::count());
    }
}
