<?php

namespace Tests\Feature\Residentes;

use App\Models\Habitacion;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidenteBajaTest extends TestCase
{
    use RefreshDatabase;

    public function test_discharge_frees_the_room_and_restore_returns_resident_without_room(): void
    {
        $user = User::factory()->role('director')->create();
        $habitacion = Habitacion::factory()->create(['tipo' => 'individual', 'estado' => 'ocupada']);
        $residente = Residente::factory()->create(['habitacion_id' => $habitacion->id]);

        $this->actingAs($user)->delete(route('residentes.destroy', $residente->id));

        $this->assertSoftDeleted($residente);
        $this->assertSame('libre', $habitacion->fresh()->estado);

        $this->actingAs($user)
            ->post(route('residentes.restaurar', $residente->id))
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted($residente);
        $this->assertNull($residente->fresh()->habitacion_id);
    }
}
