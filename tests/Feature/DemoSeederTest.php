<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Familiar;
use App\Models\Fichaje;
use App\Models\Incidencia;
use App\Models\Informe;
use App\Models\Medicacion;
use App\Models\Menu;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private function sembrar(): void
    {
        config(['sembrado.demo_password' => 'contraseña-de-prueba-16']);
        $this->seed(PermissionSeeder::class);
        $this->seed(DemoSeeder::class);
    }

    public function test_rellena_cada_pantalla_y_no_duplica_al_repetirse(): void
    {
        $this->sembrar();

        foreach ([Familiar::class, Informe::class, Incidencia::class, Actividad::class, Menu::class, Fichaje::class, Medicacion::class] as $modelo) {
            $this->assertGreaterThan(5, $modelo::count(), $modelo);
        }
        $this->assertSame(1, Medicacion::soloAnulados()->count());
        $this->assertSame(1, Medicacion::retiradas()->count());

        $familiares = Familiar::count();
        $this->seed(DemoSeeder::class);
        $this->assertSame($familiares, Familiar::count());
    }

    public function test_se_niega_a_ejecutarse_en_produccion(): void
    {
        $this->app['env'] = 'production';

        // Directo y no con $this->seed(): db:seed pide confirmación en producción y no llegaría a ejecutarse
        $this->expectException(RuntimeException::class);
        (new DemoSeeder)->run();
    }
}
