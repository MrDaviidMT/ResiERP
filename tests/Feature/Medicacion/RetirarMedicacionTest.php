<?php

namespace Tests\Feature\Medicacion;

use App\Enums\Role;
use App\Models\Log;
use App\Models\Medicacion;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Retirar una pauta (decisión clínica) la conserva en el historial; anular es para errores
class RetirarMedicacionTest extends TestCase
{
    use RefreshDatabase;

    private function pauta(User $enfermera, Residente $residente): Medicacion
    {
        $this->actingAs($enfermera)->post(route('medicacion.store', $residente->id), ['medicamento' => 'Omeprazol', 'dosis' => '20 mg']);

        return Medicacion::sole();
    }

    public function test_withdrawn_medication_leaves_the_active_list_but_stays_in_the_history(): void
    {
        $residente = Residente::factory()->create();
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $pauta = $this->pauta($enfermera, $residente);

        $this->actingAs($enfermera)
            ->post(route('medicacion.retirar', $pauta), ['motivo' => 'Fin del tratamiento pautado por el médico'])
            ->assertSessionHas('success');

        $pauta->refresh();
        $this->assertTrue($pauta->estaRetirada());
        $this->assertSame($enfermera->id, $pauta->retirado_por);
        $this->assertSame(0, $residente->medicaciones()->count());
        $this->assertSame(1, $residente->medicacionesRetiradas()->count());
        $this->assertSame('withdrawn', Log::where('loggable_type', Medicacion::class)->latest('id')->first()->action);

        $this->actingAs($enfermera)->get(route('medicacion.index', $residente->id))
            ->assertSee('Historial de pautas')
            ->assertSee('Fin del tratamiento pautado por el médico');
    }

    public function test_annulled_medication_disappears_from_both_lists(): void
    {
        $residente = Residente::factory()->create();
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $pauta = $this->pauta($enfermera, $residente);

        $this->actingAs($enfermera)->post(route('medicacion.anular', $pauta), ['motivo' => 'Pautada en el residente equivocado']);

        $this->assertSame(0, $residente->medicaciones()->count());
        $this->assertSame(0, $residente->medicacionesRetiradas()->count());
        $this->actingAs($enfermera)->get(route('medicacion.index', $residente->id))->assertSee('Mostrar anulados (1)');
    }

    public function test_auxiliary_can_view_but_not_withdraw(): void
    {
        $residente = Residente::factory()->create();
        $pauta = $this->pauta(User::factory()->role(Role::Enfermero)->create(), $residente);
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();

        $this->actingAs($auxiliar)->get(route('medicacion.index', $residente->id))->assertOk()->assertDontSee('Retirar pauta');
        $this->actingAs($auxiliar)->post(route('medicacion.retirar', $pauta), ['motivo' => 'Fin del tratamiento pautado'])->assertForbidden();

        $this->assertFalse($pauta->fresh()->estaRetirada());
    }

    public function test_medication_can_no_longer_be_deleted_or_withdrawn_twice(): void
    {
        $residente = Residente::factory()->create();
        $enfermera = User::factory()->role(Role::Enfermero)->create();
        $pauta = $this->pauta($enfermera, $residente);

        // La URL de borrado ya no existe para ningún método
        $this->actingAs($enfermera)->delete("/medicacion/{$pauta->id}")->assertNotFound();

        $this->actingAs($enfermera)->post(route('medicacion.retirar', $pauta), ['motivo' => 'Fin del tratamiento pautado']);
        $primeraRetirada = $pauta->fresh()->fecha_fin;
        $this->actingAs($enfermera)->post(route('medicacion.retirar', $pauta), ['motivo' => 'Segundo intento de retirada'])->assertSessionHas('error');

        $this->assertEquals($primeraRetirada, $pauta->fresh()->fecha_fin);
        $this->assertSame(1, Medicacion::count());
    }
}
