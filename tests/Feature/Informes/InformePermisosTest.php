<?php

namespace Tests\Feature\Informes;

use App\Enums\Role;
use App\Models\Informe;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Ver según el tipo, crear su tipo y "general", editar solo el autor, borrar solo dirección
class InformePermisosTest extends TestCase
{
    use RefreshDatabase;

    private function informe(string $tipo, ?User $autor = null, string $titulo = 'Informe'): Informe
    {
        $autor ??= User::factory()->role(Role::Director)->create();

        return tap((new Informe(['titulo' => $titulo, 'tipo' => $tipo, 'contenido' => 'Texto']))->forceFill(['user_id' => $autor->id]))->save();
    }

    public function test_auxiliary_cannot_read_a_psychological_report(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();

        $this->actingAs($auxiliar)->get(route('informes.show', $this->informe('psicologico')))->assertForbidden();
        $this->actingAs($auxiliar)->get(route('informes.show', $this->informe('general')))->assertOk();
    }

    public function test_lists_only_show_report_types_the_user_can_read(): void
    {
        $auxiliar = User::factory()->role(Role::Auxiliar)->create();
        $residente = Residente::factory()->create();
        foreach (['psicologico' => 'Evaluación cognitiva', 'general' => 'Resumen trimestral'] as $tipo => $titulo) {
            $this->informe($tipo, titulo: $titulo)->residentes()->attach($residente->id);
        }

        $this->actingAs($auxiliar)->get(route('informes.index'))
            ->assertSee('Resumen trimestral')->assertDontSee('Evaluación cognitiva');
        $this->actingAs($auxiliar)->get(route('residentes.historial', $residente))
            ->assertSee('Resumen trimestral')->assertDontSee('Evaluación cognitiva');
    }

    public function test_psychologist_can_create_a_general_report(): void
    {
        $psicologo = User::factory()->role(Role::Psicologo)->create();

        $this->actingAs($psicologo)->post(route('informes.store'), [
            'titulo' => 'Informe general', 'tipo' => 'general', 'contenido' => 'Texto',
        ])->assertSessionHas('success');

        $this->assertSame('general', Informe::sole()->tipo);
    }

    public function test_only_the_author_can_edit_a_report(): void
    {
        $autora = User::factory()->role(Role::Enfermero)->create();
        $companero = User::factory()->role(Role::Enfermero)->create();
        $informe = $this->informe('enfermeria', $autora);

        $this->actingAs($companero)->get(route('informes.edit', $informe))->assertForbidden();
        $this->actingAs($autora)->get(route('informes.edit', $informe))->assertOk();
    }

    public function test_only_the_director_can_annul_a_report(): void
    {
        $autora = User::factory()->role(Role::Enfermero)->create();
        $director = User::factory()->role(Role::Director)->create();
        $informe = $this->informe('enfermeria', $autora, 'Valoración inicial');
        $motivo = ['motivo' => 'Informe duplicado por error'];

        $this->actingAs($autora)->post(route('informes.anular', $informe), $motivo)->assertForbidden();
        $this->actingAs($director)->post(route('informes.anular', $informe), $motivo)->assertRedirect();

        // Se conserva, pero ya no figura entre los vigentes
        $this->assertSame(0, Informe::count());
        $this->assertSame(1, Informe::conAnulados()->count());
        $this->actingAs($director)->get(route('informes.index'))
            ->assertSee('Mostrar anulados (1)')
            ->assertSee('Informe duplicado por error');
    }

    public function test_reports_can_no_longer_be_deleted(): void
    {
        $director = User::factory()->role(Role::Director)->create();
        $informe = $this->informe('general', $director);

        $this->actingAs($director)->delete("/informes/{$informe->id}")->assertMethodNotAllowed();

        $this->assertSame(1, Informe::count());
    }

    public function test_director_only_creates_general_reports(): void
    {
        $director = User::factory()->role(Role::Director)->create();

        $this->actingAs($director)->post(route('informes.store'), [
            'titulo' => 'Informe', 'tipo' => 'enfermeria', 'contenido' => 'Texto',
        ])->assertSessionHas('error');

        $this->assertSame(0, Informe::count());
    }
}
