<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Log;
use App\Models\Medicacion;
use App\Models\Menu;
use App\Models\Residente;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

/** Lo repetitivo se resuelve en la plantilla y en app.css: estas pruebas impiden que vuelva a cada vista. */
class PlantillaTest extends TestCase
{
    use RefreshDatabase;

    /** @return iterable<string, string> */
    private function vistas(): iterable
    {
        /** @var SplFileInfo $fichero */
        foreach (File::allFiles(resource_path('views')) as $fichero) {
            if (! preg_match('#/(auth|errors|vendor)/#', $fichero->getPathname())) {
                yield $fichero->getRelativePathname() => $fichero->getContents();
            }
        }
    }

    public function test_las_vistas_no_montan_a_mano_lo_que_ya_da_la_plantilla(): void
    {
        foreach ($this->vistas() as $ruta => $contenido) {
            $this->assertStringNotContainsString("@push('top-nav-left')", $contenido, "{$ruta}: el enlace de volver es un parámetro de x-app-layout");
            if ($ruta !== 'components/avisos.blade.php') {
                $this->assertDoesNotMatchRegularExpression("/session\('(success|error|info|warning)'\)|errors->any/", $contenido, "{$ruta}: los avisos los pinta x-avisos");
            }
        }
    }

    public function test_los_componentes_no_se_compensan_con_utilidades_ni_con_colores_de_fabrica(): void
    {
        $sobra = '(p[xy]?-[\d.]+|rounded\S*|text-(xs|sm|base|lg)|font-\w+|bg-\S+|text-white|hover:\S+|inline-flex|items-center|space-x-\S+|cursor-pointer)';

        foreach ($this->vistas() as $ruta => $contenido) {
            $this->assertDoesNotMatchRegularExpression('/(?<![\w-])(bg|text|border)-(gray|blue|red|green|amber|yellow|rose|pink|cyan|indigo|zinc|slate)-\d/', $contenido, "{$ruta}: color que no es de la paleta");
            $this->assertDoesNotMatchRegularExpression("/class=\"[^\"{@]*\\b(boton|accion)\\b(?![\\w-])[^\"{@]*(?<=[\\s\"]){$sobra}(?=[\\s\"])/", $contenido, "{$ruta}: .boton y .accion ya definen eso");
            $this->assertStringNotContainsString('square-pen', $contenido, "{$ruta}: editar es siempre el lápiz");
        }
    }

    public function test_todo_listado_va_dentro_de_una_tarjeta(): void
    {
        foreach ($this->vistas() as $ruta => $contenido) {
            // Los parciales del historial (_*.blade.php) ya están dentro de la tarjeta de su página
            if (str_contains($contenido, 'class="tabla') && ! str_starts_with(basename($ruta), '_')) {
                $this->assertLessThan(
                    strpos($contenido, 'class="tabla'),
                    (int) strpos($contenido, 'class="tarjeta p-6"') ?: PHP_INT_MAX,
                    "{$ruta}: la tabla debe ir dentro de .tarjeta, como las demás"
                );
                if (str_contains($contenido, 'class="filtros')) {
                    $this->assertLessThan(strpos($contenido, 'class="filtros'), strpos($contenido, 'class="tarjeta p-6"'), "{$ruta}: los filtros también van dentro");
                }
            }
        }
    }

    public function test_el_enlace_a_la_ficha_ocupa_toda_la_casilla_en_todas_las_tablas(): void
    {
        foreach ($this->vistas() as $ruta => $contenido) {
            preg_match_all('/<td\b[^>]*>\s*(?:@can\([^\n]*\n\s*)?<a href="\{\{ route\(\'[\w.]+\.show\'[^"]*"(?:[^>"]|"[^"]*")*>/', $contenido, $enlaces);
            foreach ($enlaces[0] as $enlace) {
                $this->assertStringContainsString('class="enlace-fila"', $enlace, "{$ruta}: usa .enlace-fila");
            }
        }
    }

    public function test_la_flecha_de_subir_flota_y_esta_en_la_plantilla_de_todas_las_paginas(): void
    {
        $layout = File::get(resource_path('views/layouts/app.blade.php'));
        $css = File::get(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression('/id="btn-scroll-top"[^>]*aria-label="[^"]+"[^>]*class="[^"]*boton-subir/s', $layout);
        $this->assertMatchesRegularExpression('/\.boton-subir \{[^}]*position: fixed/', $css);
    }

    public function test_los_dias_de_los_menus_salen_en_espanol(): void
    {
        $this->travelTo('2026-11-04 10:00:00'); // miércoles: lleva tilde, y en inglés no se parece
        $this->seed(PermissionSeeder::class);
        Menu::create(['fecha' => '2026-11-04', 'comida' => 'Lentejas']);

        $this->actingAs(User::factory()->role(Role::Cocinero)->create())->get(route('menus.index'))
            ->assertOk()
            ->assertSee('Miércoles 04/11/2026')
            ->assertDontSee('Wednesday');
    }

    public function test_ninguna_vista_imprime_nombres_de_dia_o_mes_sin_traducir(): void
    {
        foreach ($this->vistas() as $ruta => $contenido) {
            // format() usa siempre el inglés; translatedFormat() respeta el idioma de la aplicación
            $this->assertDoesNotMatchRegularExpression("/->format\\('[^']*[lDFM][^']*'\\)/", $contenido, "{$ruta}: usa translatedFormat()");
        }
    }

    public function test_todo_rol_tiene_su_color(): void
    {
        $css = File::get(resource_path('css/app.css'));

        foreach (Role::cases() as $rol) {
            $this->assertStringContainsString(".rol-{$rol->value} ", $css);
        }
    }

    private function enfermero(): User
    {
        $this->seed(PermissionSeeder::class);

        return User::factory()->role(Role::Enfermero)->create();
    }

    public function test_el_resumen_muestra_la_medicacion_vigente_y_queda_auditado(): void
    {
        $usuario = $this->enfermero();
        $residente = Residente::factory()->create();
        Medicacion::create(['residente_id' => $residente->id, 'medicamento' => 'Fármaco vigente', 'dosis' => '1', 'desayuno' => true, 'noche' => true]);
        Medicacion::create(['residente_id' => $residente->id, 'medicamento' => 'Fármaco anulado', 'dosis' => '1'])
            ->anular('Registrada por error en este residente.');

        $this->actingAs($usuario)->get(route('residentes.show', $residente))
            ->assertOk()
            ->assertSee('Fármaco vigente')
            ->assertSee('Desayuno, noche')
            ->assertDontSee('Fármaco anulado');

        $this->assertTrue(
            Log::where('action', 'viewed')->where('user_id', $usuario->id)->where('loggable_id', $residente->id)->exists(),
            'Enseñar medicación en el resumen es una consulta clínica: debe auditarse'
        );
    }

    public function test_sin_permiso_clinico_ni_se_ve_la_medicacion_ni_se_audita_como_consulta_clinica(): void
    {
        $this->seed(PermissionSeeder::class);
        $usuario = User::factory()->role(Role::Recepcionista)->create();
        $this->assertFalse($usuario->canAny([Permission::MedicacionVer, Permission::DiagnosticosVer]));
        $residente = Residente::factory()->create();
        Medicacion::create(['residente_id' => $residente->id, 'medicamento' => 'Fármaco privado', 'dosis' => '1']);

        $this->actingAs($usuario)->get(route('residentes.show', $residente))
            ->assertOk()
            ->assertDontSee('Fármaco privado')
            ->assertDontSee(route('medicacion.index', $residente->id));

        $this->assertFalse(Log::where('action', 'viewed')->where('loggable_id', $residente->id)->exists());
    }

    public function test_la_cabecera_del_residente_no_enseña_el_dni_sin_permiso(): void
    {
        $this->seed(PermissionSeeder::class);
        $usuario = User::factory()->role(Role::Cocinero)->create();
        $residente = Residente::factory()->create(['dni' => '12345678Z']);

        $this->actingAs($usuario)->get(route('dieta.index', $residente))
            ->assertOk()
            ->assertDontSee('12345678Z')
            ->assertSee('aria-current="page"', false);
    }
}
