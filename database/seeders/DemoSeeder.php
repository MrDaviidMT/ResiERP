<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Actividad;
use App\Models\Alergia;
use App\Models\ConstanteVital;
use App\Models\CuidadoDiario;
use App\Models\Diagnostico;
use App\Models\Dieta;
use App\Models\Familiar;
use App\Models\Fichaje;
use App\Models\Habitacion;
use App\Models\Incidencia;
use App\Models\Informe;
use App\Models\Medicacion;
use App\Models\Menu;
use App\Models\Residente;
use App\Models\User;
use App\Models\Visita;
use App\Models\VisitaMedica;
use App\Rules\DocumentoIdentidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Datos de demostración para ver todas las pantallas con contenido. Se ejecuta a mano y nunca
 * en un despliegue: `php artisan db:seed --class=DemoSeeder`.
 *
 * Todo es inventado: personas, teléfonos (rango 600 000 xxx), correos (dominio reservado
 * example.test) y DNI (generados con su letra de control para pasar la validación).
 * Las fechas son relativas a hoy para que los paneles no queden vacíos con el tiempo.
 */
class DemoSeeder extends Seeder
{
    private const MARCA = 'marta.ibanez@example.test';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder no se ejecuta en producción: mezclaría datos inventados con los reales.');
        }

        // Una sola vez: repetirlo duplicaría el historial clínico
        if (Familiar::where('email', self::MARCA)->exists()) {
            return;
        }

        $personal = $this->personal();
        $residentes = $this->residentes();

        foreach ($residentes->take(8)->values() as $i => $residente) {
            $this->historial($residente, $i, $personal);
        }

        $this->medicacionFueraDePauta($residentes->first(), $personal);
        $this->familiaresYVisitas($residentes, $personal);
        $this->dietas($residentes);
        $this->informes($residentes, $personal);
        $this->incidencias($residentes, $personal);
        $this->actividades($residentes, $personal);
        $this->menus();
        $this->fichajes($personal);

        auth()->forgetUser();
    }

    /** @return array<string, User> */
    private function personal(): array
    {
        $personal = [];
        foreach (Role::deNegocio() as $rol) {
            $personal[$rol->value] = User::role($rol->value)->first() ?? $this->empleado($rol, ucfirst($rol->label()), "{$rol->value}@residencia.test");
        }

        // Para ver en la lista los dos casos especiales: sin verificar y dado de baja
        $this->empleado(Role::Auxiliar, 'Lucía Ferrer Gil', 'lucia.ferrer@example.test', verificado: false);
        $this->empleado(Role::Mantenimiento, 'Marcos Vidal Roca', 'marcos.vidal@example.test')->forceFill(['activo' => false])->save();

        return $personal;
    }

    private function empleado(Role $rol, string $nombre, string $email, bool $verificado = true): User
    {
        $usuario = User::forceCreate([
            'name' => $nombre,
            'email' => $email,
            'password' => Hash::make((string) config('sembrado.demo_password', 'sin-contraseña-'.bin2hex(random_bytes(8)))),
            'email_verified_at' => $verificado ? now() : null,
        ]);
        $usuario->assignRole($rol->value);

        return $usuario;
    }

    private function residentes()
    {
        if (Residente::count() < 8) {
            $nombres = [['Antonio', 'Ruiz Mena'], ['Carmen', 'Vega Soler'], ['José', 'Marín Prieto'], ['Dolores', 'Campos Nieto'], ['Manuel', 'Herrero Lara'], ['Pilar', 'Ortega Rey'], ['Francisco', 'Cano Bravo'], ['Rosa', 'Molina Pardo']];
            foreach ($nombres as $i => [$nombre, $apellidos]) {
                $habitacion = Habitacion::firstOrCreate(['numero' => (string) (201 + $i)], ['tipo' => $i % 2 ? 'doble' : 'individual', 'estado' => 'libre']);
                Residente::create([
                    'nombre' => $nombre, 'apellidos' => $apellidos, 'dni' => $this->dni(30000000 + $i),
                    'nacimiento' => now()->subYears(72 + $i * 2)->subDays($i * 37), 'ingreso' => now()->subMonths(4 + $i * 3),
                    'grupo_sanguineo' => ['A+', 'O+', 'B+', 'O-'][$i % 4], 'dependencia' => ['grado_1', 'grado_2', 'grado_3'][$i % 3],
                    'habitacion_id' => $habitacion->id,
                ]);
                $habitacion->sincronizarEstado();
            }
        }

        return Residente::whereNotNull('habitacion_id')->orderBy('id')->get();
    }

    private function historial(Residente $residente, int $i, array $personal): void
    {
        $enfermero = $personal['enfermero'];
        $catalogo = [
            ['Hipertensión arterial', 'cronico', 'Control de sal y enalapril', 'Medicina interna'],
            ['Artrosis de rodilla', 'degenerativo', 'Fisioterapia y paracetamol a demanda', 'Traumatología'],
            ['Diabetes mellitus tipo 2', 'metabolico', 'Dieta diabética y metformina', 'Endocrinología'],
            ['Deterioro cognitivo leve', 'degenerativo', 'Estimulación cognitiva semanal', 'Neurología'],
            ['Infección urinaria', 'infeccioso', 'Antibiótico durante 7 días', 'Medicina interna'],
            ['Fractura de cadera', 'traumatico', 'Rehabilitación y control del dolor', 'Traumatología'],
        ];
        auth()->setUser($enfermero);

        foreach ([$i, $i + 2] as $n => $indice) {
            [$nombre, $tipo, $tratamiento, $especialidad] = $catalogo[$indice % count($catalogo)];
            Diagnostico::create([
                'residente_id' => $residente->id, 'nombre' => $nombre, 'tipo' => $tipo, 'activo' => $n === 0 || $i % 3 !== 0,
                'descripcion' => "Seguimiento habitual de {$nombre}.", 'tratamiento' => $tratamiento,
                'medico' => ['Dra. Paredes', 'Dr. Alonso', 'Dra. Sanz'][$indice % 3], 'especialidad' => $especialidad,
                'fecha_diagnostico' => now()->subMonths(3 + $indice * 4),
            ]);
        }

        [$alergeno, $reaccion, $severidad] = [['Penicilina', 'Erupción cutánea', 'grave'], ['Látex', 'Picor en la zona de contacto', 'leve'], ['Marisco', 'Hinchazón de labios', 'moderada'], ['Ibuprofeno', 'Molestias digestivas', 'leve']][$i % 4];
        Alergia::create(['residente_id' => $residente->id, 'alergeno' => $alergeno, 'reaccion' => $reaccion, 'severidad' => $severidad, 'fecha_deteccion' => now()->subYears(1 + $i), 'activa' => true]);

        for ($d = 0; $d < 5; $d++) {
            ConstanteVital::create([
                'residente_id' => $residente->id, 'tension_sistolica' => 118 + ($i + $d * 3) % 24, 'tension_diastolica' => 70 + ($i + $d) % 14,
                'frecuencia_cardiaca' => 64 + ($i * 2 + $d) % 22, 'temperatura' => 36 + (($i + $d) % 9) / 10, 'peso' => 58 + $i * 3.5,
                'saturacion_oxigeno' => 94 + ($i + $d) % 5, 'glucosa' => 90 + ($i * 7 + $d * 5) % 50,
                'fecha_registro' => now()->subDays($d)->setTime(8, 30), 'observaciones' => $d === 0 ? 'Toma de la mañana, sin incidencias.' : null,
            ]);
        }

        VisitaMedica::create(['residente_id' => $residente->id, 'medico' => 'Dr. Alonso', 'especialidad' => 'Cardiología', 'motivo' => 'Revisión semestral', 'fecha_visita' => now()->subDays(12 + $i)->setTime(10, 0)]);
        VisitaMedica::create(['residente_id' => $residente->id, 'medico' => 'Dra. Sanz', 'especialidad' => 'Geriatría', 'motivo' => 'Valoración de seguimiento', 'fecha_visita' => now()->addDays(3 + $i)->setTime(11, 30)]);

        $pautas = [['Enalapril 10 mg', '1 comprimido', ['desayuno'], 'Controlar la tensión antes de la toma.'], ['Metformina 850 mg', '1 comprimido', ['desayuno', 'cena'], 'Con las comidas.'], ['Omeprazol 20 mg', '1 cápsula', ['desayuno'], 'En ayunas.'], ['Paracetamol 1 g', '1 comprimido', ['comida', 'noche'], 'Si hay dolor.'], ['Lorazepam 1 mg', '1 comprimido', ['noche'], null]];
        foreach ([$i, $i + 1, $i + 3] as $indice) {
            [$medicamento, $dosis, $tomas, $observaciones] = $pautas[$indice % count($pautas)];
            Medicacion::create(['residente_id' => $residente->id, 'medicamento' => $medicamento, 'dosis' => $dosis, 'observaciones' => $observaciones] + array_fill_keys($tomas, true));
        }

        auth()->setUser($personal['auxiliar']);
        for ($d = 0; $d < 5; $d++) {
            CuidadoDiario::create(['residente_id' => $residente->id, 'fecha' => now()->subDays($d + 1), 'comio_bien' => ($i + $d) % 4 !== 0, 'se_baño' => ($i + $d) % 3 !== 0, 'observaciones' => ($i + $d) % 4 === 0 ? 'Ha comido menos de lo habitual.' : 'Jornada sin incidencias.']);
        }
    }

    /** Una pauta retirada y otra anulada, para ver el historial y los diálogos de motivo. */
    private function medicacionFueraDePauta(Residente $residente, array $personal): void
    {
        auth()->setUser($personal['enfermero']);
        Medicacion::create(['residente_id' => $residente->id, 'medicamento' => 'Amoxicilina 500 mg', 'dosis' => '1 cápsula', 'desayuno' => true, 'cena' => true])
            ->retirar('Tratamiento de siete días finalizado sin complicaciones.');
        Medicacion::create(['residente_id' => $residente->id, 'medicamento' => 'Ibuprofeno 600 mg', 'dosis' => '1 comprimido', 'comida' => true])
            ->anular('Registrada por error en este residente, corresponde a otro.');
    }

    private function familiaresYVisitas($residentes, array $personal): void
    {
        $personas = [['Marta', 'Ibáñez Roldán', 'hija'], ['Javier', 'Núñez Pastor', 'hijo'], ['Elena', 'Crespo Aguilar', 'sobrina'], ['Raúl', 'Santos Reina', 'hijo'], ['Sonia', 'Benítez Lozano', 'hija'], ['Pablo', 'Domínguez Vera', 'nieto'], ['Teresa', 'Gallego Peña', 'hermana'], ['Andrés', 'Rubio Calvo', 'hijo'], ['Inés', 'Fuentes Mora', 'nieta'], ['Óscar', 'Pascual Rico', 'sobrino']];
        auth()->setUser($personal['recepcionista']);

        foreach ($personas as $i => [$nombre, $apellidos, $parentesco]) {
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT', mb_strtolower($nombre.'.'.strtok($apellidos, ' ')));
            $familiar = Familiar::create([
                'nombre' => $nombre, 'apellidos' => $apellidos, 'dni' => $this->dni(40000000 + $i), 'telefono' => sprintf('600000%03d', $i + 100),
                'email' => $i === 0 ? self::MARCA : "{$slug}@example.test", 'direccion' => 'Calle Inventada '.($i + 1),
            ]);
            $residente = $residentes[$i % min(8, $residentes->count())];
            $familiar->residentes()->attach($residente->id, ['parentesco' => $parentesco, 'es_emergencia' => $i % 2 === 0, 'autorizado' => $i % 5 !== 4]);

            Visita::create(['residente_id' => $residente->id, 'familiar_id' => $familiar->id, 'nombre_visitante' => "{$nombre} {$apellidos}", 'documento' => $familiar->dni, 'entrada' => now()->subDays($i + 1)->setTime(17, 0), 'salida' => now()->subDays($i + 1)->setTime(18, 15), 'observaciones' => 'Visita en la sala común.']);
        }

        // Una visita en curso y otra de alguien que no es familiar
        Visita::create(['residente_id' => $residentes[0]->id, 'nombre_visitante' => 'Hugo Lázaro Vives', 'documento' => $this->dni(40000099), 'entrada' => now()->subMinutes(35), 'observaciones' => 'Amigo del residente.']);
    }

    private function dietas($residentes): void
    {
        $planes = [['diabetica', 'Lactosa', 'Le gusta la fruta de temporada.', 'Controlar raciones de hidratos.'], ['sin_sal', null, 'Prefiere el pescado a la carne.', null], ['triturada', 'Frutos secos', null, 'Disfagia: supervisar las comidas.'], ['normal', null, 'Café descafeinado por las tardes.', null], ['blanda', null, null, 'Problemas de masticación.'], ['hipocalorica', 'Marisco', 'No le gusta el pollo.', 'Objetivo: bajar 3 kg.']];
        foreach ($residentes->take(6)->values() as $i => $residente) {
            [$tipo, $alergias, $preferencias, $observaciones] = $planes[$i];
            Dieta::updateOrCreate(['residente_id' => $residente->id], ['tipo' => $tipo, 'alergias_alimentarias' => $alergias, 'preferencias' => $preferencias, 'observaciones' => $observaciones]);
        }
    }

    private function informes($residentes, array $personal): void
    {
        $informes = [
            ['Valoración psicológica inicial', 'psicologico', 'psicologo'], ['Seguimiento del estado de ánimo', 'psicologico', 'psicologo'],
            ['Informe social para ayuda a la dependencia', 'social', 'trabajador_social'], ['Situación familiar y apoyos', 'social', 'trabajador_social'],
            ['Plan de rehabilitación de cadera', 'fisioterapia', 'fisioterapeuta'], ['Evolución de la movilidad', 'fisioterapia', 'fisioterapeuta'],
            ['Informe trimestral de la residencia', 'general', 'director'], ['Resumen de incidencias del mes', 'general', 'director'],
            ['Protocolo de caídas revisado', 'general', 'director'], ['Valoración funcional anual', 'fisioterapia', 'fisioterapeuta'],
        ];
        foreach ($informes as $i => [$titulo, $tipo, $autor]) {
            auth()->setUser($personal[$autor]);
            $informe = (new Informe(['titulo' => $titulo, 'tipo' => $tipo, 'contenido' => "Evolución estable durante el periodo.\n\nSe mantiene el plan de cuidados vigente y se revisará en la próxima valoración."]))
                ->forceFill(['user_id' => $personal[$autor]->id]);
            $informe->save();
            $informe->residentes()->attach($residentes->slice($i % 6, 1 + $i % 3)->pluck('id'));
        }
    }

    private function incidencias($residentes, array $personal): void
    {
        $lista = [
            ['Caída sin consecuencias en el pasillo', 'sanitaria', 'grave', 'resuelta', 'residente'], ['Fiebre persistente por la noche', 'sanitaria', 'urgente', 'en_proceso', 'residente'],
            ['Reacción cutánea tras la cena', 'sanitaria', 'grave', 'pendiente', 'residente'], ['Rechazo de la medicación', 'sanitaria', 'leve', 'pendiente', 'residente'],
            ['Grifo del baño que gotea', 'infraestructura', 'leve', 'pendiente', 'habitacion'], ['Persiana atascada', 'infraestructura', 'leve', 'en_proceso', 'habitacion'],
            ['Timbre de llamada averiado', 'infraestructura', 'urgente', 'en_proceso', 'habitacion'], ['Radiador sin calentar', 'infraestructura', 'grave', 'resuelta', 'habitacion'],
            ['Derrame en la sala común', 'limpieza', 'leve', 'resuelta', null], ['Olor persistente en el baño de la planta 2', 'limpieza', 'leve', 'pendiente', null],
            ['Falta de personal en el turno de noche', 'personal', 'grave', 'en_proceso', null], ['Discrepancia en el cuadrante de turnos', 'personal', 'leve', 'resuelta', null],
            ['Ascensor con ruido anómalo', 'otros', 'grave', 'pendiente', null], ['Pedido de farmacia con retraso', 'otros', 'leve', 'resuelta', null],
        ];
        auth()->setUser($personal['auxiliar']);
        foreach ($lista as $i => [$titulo, $categoria, $prioridad, $estado, $vinculo]) {
            $residente = $residentes[$i % min(8, $residentes->count())];
            $incidencia = new Incidencia([
                'titulo' => $titulo, 'categoria' => $categoria, 'prioridad' => $prioridad, 'descripcion' => 'Detectada durante el turno y comunicada al responsable.',
                'residente_id' => $vinculo === 'residente' ? $residente->id : null, 'habitacion_id' => $vinculo === 'habitacion' ? $residente->habitacion_id : null,
            ]);
            $incidencia->forceFill(['user_id' => $personal['auxiliar']->id, 'estado' => $estado, 'fecha_resolucion' => $estado === 'resuelta' ? now()->subDays($i % 5) : null])->save();
        }

        auth()->setUser($personal['director']);
        Incidencia::query()->latest('id')->first()->anular('Duplicada: ya se registró en la incidencia anterior.');
    }

    private function actividades($residentes, array $personal): void
    {
        $lista = [['Taller de memoria', 'Ejercicios de estimulación cognitiva.', 2, '11:00'], ['Gimnasia suave', 'Movilidad articular en silla.', 1, '10:00'], ['Bingo de la tarde', 'Juego por equipos en la sala común.', 4, '17:00'], ['Musicoterapia', 'Canciones populares y ritmo.', 6, '12:00'], ['Paseo por el jardín', 'Salida acompañada si el tiempo lo permite.', -2, '11:30'], ['Taller de manualidades', 'Decoración de la planta.', -5, '16:30'], ['Cine de sobremesa', 'Película clásica con merienda.', -9, '16:00']];
        foreach ($lista as $i => [$titulo, $descripcion, $dias, $hora]) {
            $actividad = Actividad::create(['user_id' => $personal['trabajador_social']->id, 'titulo' => $titulo, 'descripcion' => $descripcion, 'fecha' => now()->addDays($dias), 'hora' => $hora]);
            $actividad->residentes()->attach($residentes->slice($i % 4, 3 + $i % 4)->pluck('id'));
        }
    }

    private function menus(): void
    {
        $comidas = [['Lentejas estofadas', 'Merluza al horno'], ['Crema de calabaza', 'Pollo asado'], ['Arroz con verduras', 'Tortilla de patata'], ['Sopa de fideos', 'Albóndigas en salsa'], ['Judías verdes', 'Lomo a la plancha'], ['Garbanzos con espinacas', 'Pescado rebozado'], ['Puré de verduras', 'Pavo en salsa']];
        for ($d = -3; $d < 11; $d++) {
            [$primero, $segundo] = $comidas[($d + 3) % 7];
            Menu::firstOrCreate(['fecha' => now()->addDays($d)->toDateString()], [
                'desayuno' => 'Café con leche y tostada', 'comida' => "{$primero} y {$segundo}", 'merienda' => 'Yogur y fruta', 'cena' => 'Sopa juliana y tortilla francesa',
                'observaciones' => $d === 0 ? 'Disponible versión triturada y sin sal.' : null,
            ]);
        }
    }

    /** @param array<string, User> $personal */
    private function fichajes(array $personal): void
    {
        foreach (array_values($personal) as $n => $usuario) {
            for ($d = 1; $d <= 8; $d++) {
                if (now()->subDays($d)->isSunday() && $n % 2) {
                    continue;
                }
                $entrada = now()->subDays($d)->setTime(7 + $n % 3, ($n * 7 + $d * 3) % 60);
                Fichaje::create(['user_id' => $usuario->id, 'entrada' => $entrada, 'salida' => $entrada->copy()->addHours(7)->addMinutes(($n + $d) % 40), 'ip_address' => '192.168.1.'.(50 + $n), 'notas' => $d === 1 ? 'Turno habitual.' : null]);
            }
        }
    }

    private function dni(int $numero): string
    {
        return $numero.DocumentoIdentidad::letraDe($numero);
    }
}
