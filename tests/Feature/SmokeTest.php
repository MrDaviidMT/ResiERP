<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Actividad;
use App\Models\Diagnostico;
use App\Models\Familiar;
use App\Models\Fichaje;
use App\Models\Incidencia;
use App\Models\Informe;
use App\Models\Residente;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

// Recorre todas las páginas GET con datos reales: con el modo estricto de Eloquent activo,
// cualquier consulta N+1 o atributo inexistente hace que la página devuelva un 500
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_renders_without_server_errors(): void
    {
        $this->seed();
        $admin = User::role(Role::Superadmin)->firstOrFail();
        $this->actingAs($admin);

        $residente = Residente::whereNotNull('habitacion_id')->firstOrFail();

        // Dos registros de cada tipo: Laravel solo detecta la carga diferida (N+1) en colecciones de 2 o más
        foreach ([1, 2] as $i) {
            $familiar = Familiar::create(['nombre' => "Familiar {$i}", 'apellidos' => 'Pérez', 'dni' => "1234567{$i}Z", 'telefono' => '600000000']);
            $familiar->residentes()->attach($residente->id, ['parentesco' => 'hija']);
            $informe = tap((new Informe(['titulo' => "Informe {$i}", 'tipo' => 'general', 'contenido' => 'Texto']))->forceFill(['user_id' => $admin->id]))->save();
            $informe->residentes()->attach($residente->id);
            $actividad = Actividad::create(['user_id' => $admin->id, 'titulo' => "Taller {$i}", 'fecha' => now(), 'hora' => '10:00']);
            $actividad->residentes()->attach($residente->id);
            $incidencia = Incidencia::create(['categoria' => 'sanitaria', 'titulo' => "Caída {$i}", 'prioridad' => 'leve', 'residente_id' => $residente->id]);
            $fichaje = Fichaje::create(['user_id' => $admin->id, 'entrada' => now()->subHours(8 + $i), 'salida' => now()->subHours($i)]);
            Visita::create(['residente_id' => $residente->id, 'familiar_id' => $familiar->id, 'nombre_visitante' => "Visitante {$i}", 'documento' => "1234567{$i}Z", 'entrada' => now()]);
            // Registros anulados: la sección "Mostrar anulados" también debe funcionar sin N+1
            Diagnostico::create(['residente_id' => $residente->id, 'nombre' => "Diagnóstico {$i}", 'tipo' => 'cronico', 'fecha_diagnostico' => now()])
                ->anular('Registrado por error en este residente');
        }

        $parametros = [
            'residente' => $residente->id, 'residente_id' => $residente->id, 'familiar' => $familiar->id,
            'informe' => $informe->id, 'actividad' => $actividad->id, 'incidencia' => $incidencia->id,
            'fichaje' => $fichaje->id, 'user' => $admin->id, 'habitacion' => $residente->habitacion_id,
        ];

        $comprobadas = 0;
        foreach (Route::getRoutes() as $ruta) {
            if (! in_array('GET', $ruta->methods()) || ! in_array('auth', $ruta->gatherMiddleware())) {
                continue;
            }
            // Las rutas firmadas (verificación de email) necesitan una URL generada, no aplican aquí
            if (in_array('signed', $ruta->gatherMiddleware())) {
                continue;
            }

            $uri = preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $parametros[$m[1]] ?? 1, $ruta->uri());
            $respuesta = $this->get('/'.ltrim($uri, '/'));

            // Las descargas (StreamedResponse) no tienen la propiedad "exception" de las respuestas normales
            $base = $respuesta->baseResponse;
            $excepcion = property_exists($base, 'exception') ? $base->exception : null;

            $this->assertLessThan(500, $respuesta->getStatusCode(), "GET /{$uri}: ".($excepcion?->getMessage() ?? ''));

            // La CSP declara script-src sin 'unsafe-inline' (hallazgo S13), así que el navegador
            // ignora cualquier manejador escrito en el HTML. Un onclick aquí no falla en los tests
            // pero deja el botón muerto en producción: así se colaron 13, y entre ellos el de
            // cerrar sesión, que acabó haciendo GET /logout y devolviendo un 405.
            if (str_contains((string) $base->headers->get('Content-Type'), 'text/html')) {
                // Una directiva de Blade que llega al navegador es que no se procesó. Pasó al
                // meter data-json='@json(...)' dentro de la etiqueta de un componente: ahí el
                // valor del atributo se compila como cadena PHP, así que @json no se ejecuta y
                // el botón se quedaba muerto sin que fallara ningún test.
                $this->assertDoesNotMatchRegularExpression(
                    '/@(?:json|if|can|foreach|endif|csrf)\s*[(\s]/',
                    (string) $respuesta->getContent(),
                    "GET /{$uri}: llega una directiva de Blade sin procesar al HTML."
                );

                $this->assertDoesNotMatchRegularExpression(
                    '/\son(?:click|submit|change|input|keyup|keydown|focus|blur|load)\s*=/i',
                    (string) $respuesta->getContent(),
                    "GET /{$uri}: tiene un manejador de eventos en el HTML; la CSP lo bloquea en el navegador. ".
                    'Pásalo a resources/js con un atributo data-* y un listener delegado.'
                );
            }

            $comprobadas++;
        }

        $this->assertGreaterThan(30, $comprobadas);
    }
}
