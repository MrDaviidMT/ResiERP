<?php

namespace Tests\Feature\Auditoria;

use App\Enums\Role;
use App\Models\Dieta;
use App\Models\Familiar;
use App\Models\Log;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// D7 y D8 (auditoría): cambiar una dieta o autorizar a un familiar deja rastro de quién lo hizo
class AuditoriaDietaVinculosTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_a_diet_is_audited(): void
    {
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $residente = Residente::factory()->create();
        $dieta = ['tipo' => 'diabetica', 'alergias_alimentarias' => 'Lactosa'];

        $this->actingAs($enfermera)->post(route('dieta.store', $residente), $dieta);
        $this->actingAs($enfermera)->post(route('dieta.store', $residente), ['tipo' => 'sin_sal', 'alergias_alimentarias' => 'Lactosa y frutos secos']);

        $alta = Log::where('loggable_type', Dieta::class)->where('action', 'created')->sole();
        $cambio = Log::where('loggable_type', Dieta::class)->where('action', 'updated')->sole();
        $this->assertSame($enfermera->id, $cambio->user_id);
        $this->assertSame('diabetica', $cambio->changes['old']['tipo']);
        $this->assertSame('sin_sal', $cambio->changes['new']['tipo']);
        $this->assertNotNull($alta);
    }

    public function test_authorizing_a_relative_is_audited(): void
    {
        $ts = User::factory()->role(Role::TrabajadorSocial)->create();
        $residente = Residente::factory()->create();
        $familiar = Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000']);

        $this->actingAs($ts)->post(route('familiares.vincular-existente', $residente), [
            'familiar_id' => $familiar->id, 'parentesco' => 'Hija', 'autorizado' => '1',
        ])->assertSessionHasNoErrors();

        $log = Log::where('action', 'linked')->sole();
        $this->assertSame($ts->id, $log->user_id);
        $this->assertSame($residente->id, $log->changes['residente_id']);
        $this->assertTrue($log->changes['autorizado']);
        $this->assertSame('Hija', $log->changes['parentesco']);
    }

    public function test_changing_relative_links_is_audited(): void
    {
        $ts = User::factory()->role(Role::TrabajadorSocial)->create();
        $residente = Residente::factory()->create();
        $familiar = Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000']);
        $familiar->residentes()->attach($residente->id, ['parentesco' => 'Hija', 'autorizado' => false]);

        $this->actingAs($ts)->put(route('familiares.update', $familiar), [
            'nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000',
            'vinculaciones' => [['residente_id' => $residente->id, 'parentesco' => 'Hija', 'autorizado' => '1', 'es_emergencia' => '0']],
        ])->assertSessionHasNoErrors();

        $log = Log::where('action', 'links_synced')->sole();
        $this->assertSame($ts->id, $log->user_id);
        $this->assertTrue($log->changes['vinculos'][0]['autorizado']);
        $this->assertTrue((bool) $familiar->residentes()->sole()->pivot->autorizado);
    }

    public function test_audit_screen_labels_link_actions_in_spanish(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $residente = Residente::factory()->create();
        $familiar = Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000']);
        $this->actingAs($director)->post(route('familiares.vincular-existente', $residente), [
            'familiar_id' => $familiar->id, 'parentesco' => 'Hija', 'autorizado' => '1',
        ]);

        $this->actingAs($director)->get(route('logs.index', ['action' => 'linked']))
            ->assertOk()
            ->assertSee('Vinculación')
            ->assertSee('autorizado')
            ->assertDontSee('linked</span>', false);
    }
}
