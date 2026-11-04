<?php

namespace Tests\Feature\Visitas;

use App\Enums\Role;
use App\Models\Familiar;
use App\Models\Residente;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// D11 (auditoría): una visita no puede atribuirse a un familiar que no está vinculado
// a ese residente o que no está autorizado a visitarle
class VisitanteAutorizadoTest extends TestCase
{
    use RefreshDatabase;

    private function familiar(string $dni = '12345678Z'): Familiar
    {
        return Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => $dni, 'telefono' => '600000000']);
    }

    private function datos(?int $familiarId): array
    {
        return ['familiar_id' => $familiarId, 'nombre_visitante' => 'Ana Pérez', 'documento' => '12345678Z', 'entrada' => now()->toDateTimeString()];
    }

    public function test_authorized_relative_can_be_registered(): void
    {
        $recepcion = User::factory()->role(Role::Recepcionista)->create();
        $residente = Residente::factory()->create();
        $familiar = $this->familiar();
        $familiar->residentes()->attach($residente->id, ['parentesco' => 'hija', 'autorizado' => true]);

        $this->actingAs($recepcion)->post(route('visitas.store', $residente), $this->datos($familiar->id))
            ->assertSessionHas('success');

        $this->assertSame($familiar->id, Visita::sole()->familiar_id);
    }

    public function test_unauthorized_relative_is_rejected(): void
    {
        $recepcion = User::factory()->role(Role::Recepcionista)->create();
        $residente = Residente::factory()->create();
        $familiar = $this->familiar();
        $familiar->residentes()->attach($residente->id, ['parentesco' => 'hija', 'autorizado' => false]);

        $this->actingAs($recepcion)->post(route('visitas.store', $residente), $this->datos($familiar->id))
            ->assertSessionHasErrors('familiar_id');

        $this->assertSame(0, Visita::count());
    }

    public function test_relative_of_another_resident_is_rejected(): void
    {
        $recepcion = User::factory()->role(Role::Recepcionista)->create();
        $residente = Residente::factory()->create();
        $otro = Residente::factory()->create();
        $familiar = $this->familiar();
        $familiar->residentes()->attach($otro->id, ['parentesco' => 'hijo', 'autorizado' => true]);

        $this->actingAs($recepcion)->post(route('visitas.store', $residente), $this->datos($familiar->id))
            ->assertSessionHasErrors('familiar_id');

        $this->assertSame(0, Visita::count());
    }

    public function test_walk_in_visitor_without_relative_is_allowed(): void
    {
        $recepcion = User::factory()->role(Role::Recepcionista)->create();
        $residente = Residente::factory()->create();

        $this->actingAs($recepcion)->post(route('visitas.store', $residente), $this->datos(null))
            ->assertSessionHas('success');

        $this->assertNull(Visita::sole()->familiar_id);
    }

    public function test_the_form_only_offers_authorized_relatives(): void
    {
        $recepcion = User::factory()->role(Role::Recepcionista)->create();
        $residente = Residente::factory()->create();
        $autorizada = $this->familiar('11111111H');
        $autorizada->residentes()->attach($residente->id, ['parentesco' => 'hija', 'autorizado' => true]);
        $noAutorizado = Familiar::create(['nombre' => 'Luis', 'apellidos' => 'Gómez', 'dni' => '22222222J', 'telefono' => '600000001']);
        $noAutorizado->residentes()->attach($residente->id, ['parentesco' => 'hijo', 'autorizado' => false]);

        $this->actingAs($recepcion)->get(route('visitas.index', $residente))
            ->assertSee('Ana Pérez')
            ->assertDontSee('Luis Gómez');
    }
}
