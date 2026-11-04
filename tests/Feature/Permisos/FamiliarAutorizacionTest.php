<?php

namespace Tests\Feature\Permisos;

use App\Enums\Role;
use App\Models\Familiar;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Si no llega el dato de autorización, el familiar NO queda autorizado (fail-closed)
class FamiliarAutorizacionTest extends TestCase
{
    use RefreshDatabase;

    private function familiar(): Familiar
    {
        return Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000']);
    }

    public function test_unchecked_authorization_is_saved_as_not_authorized(): void
    {
        $ts = User::factory()->role(Role::TrabajadorSocial)->create();
        $residente = Residente::factory()->create();
        $familiar = $this->familiar();

        // La casilla desmarcada no se envía
        $this->actingAs($ts)->post(route('familiares.vincular-existente', $residente), [
            'familiar_id' => $familiar->id, 'parentesco' => 'Hija',
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $residente->familiares()->sole()->pivot->autorizado);
    }

    public function test_checked_authorization_is_saved_as_authorized(): void
    {
        $ts = User::factory()->role(Role::TrabajadorSocial)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($ts)->post(route('familiares.vincular-existente', $residente), [
            'familiar_id' => $this->familiar()->id, 'parentesco' => 'Hija', 'autorizado' => '1',
        ]);

        $this->assertTrue((bool) $residente->familiares()->sole()->pivot->autorizado);
    }

    public function test_link_without_authorization_flag_is_rejected_instead_of_authorized(): void
    {
        $ts = User::factory()->role(Role::TrabajadorSocial)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($ts)->post(route('familiares.storeGeneral'), [
            'nombre' => 'Luis', 'apellidos' => 'Gómez', 'dni' => '87654321X', 'telefono' => '600000001',
            'vinculaciones' => [['residente_id' => $residente->id, 'parentesco' => 'Hijo', 'es_emergencia' => '0']],
        ])->assertSessionHasErrors('vinculaciones.0.autorizado');

        $this->assertSame(0, Familiar::count());
    }

    public function test_database_default_is_not_authorized(): void
    {
        $residente = Residente::factory()->create();

        $residente->familiares()->attach($this->familiar()->id, ['parentesco' => 'Hija']);

        $this->assertFalse((bool) $residente->familiares()->sole()->pivot->autorizado);
    }
}
