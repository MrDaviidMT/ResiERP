<?php

namespace App\Services;

use App\Models\Familiar;
use App\Models\Fichaje;
use App\Models\Log;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Aplica la política de conservación (RGPD art. 5.1.e). Ver config/conservacion.php.
 *
 * Al vencer el plazo:
 * - Residentes: se anonimizan. Se conserva el contenido clínico sin poder identificar a la persona,
 *   para que la residencia mantenga sus estadísticas; se eliminan nombre, documento, dirección,
 *   vínculos familiares y los textos libres, donde suelen aparecer nombres.
 * - Cuentas de empleados: se anonimizan (la fila se mantiene, así la auditoría antigua sigue
 *   siendo coherente y las claves foráneas no se rompen).
 * - Fichajes, auditoría y familiares sin vínculo: se borran.
 *
 * En modo simulación solo se cuenta lo que se haría.
 */
class PoliticaConservacion
{
    public function __construct(private bool $simulacion) {}

    /** @return array<string, int> acciones realizadas (o que se realizarían) por tipo de dato */
    public function aplicar(): array
    {
        $anios = config('conservacion.anios');

        return [
            'residentes_anonimizados' => $this->anonimizarResidentes($anios['historia_clinica']),
            'cuentas_anonimizadas' => $this->anonimizarCuentas($anios['cuentas_empleados']),
            'fichajes_borrados' => $this->borrarFichajes($anios['registro_jornada']),
            'auditoria_borrada' => $this->borrarAuditoria($anios['auditoria']),
            'familiares_borrados' => $this->borrarFamiliares($anios['familiares_sin_vinculo']),
        ];
    }

    /** Residentes dados de baja (o fallecidos) hace más de N años. */
    private function anonimizarResidentes(int $anios): int
    {
        $limite = now()->subYears($anios);

        $residentes = Residente::onlyTrashed()
            ->whereNull('anonimizado_at')
            ->where(fn ($q) => $q->where('deleted_at', '<=', $limite)->orWhere('fecha_defuncion', '<=', $limite))
            ->get();

        if ($this->simulacion) {
            return $residentes->count();
        }

        foreach ($residentes as $residente) {
            DB::transaction(fn () => $this->anonimizarResidente($residente));
        }

        return $residentes->count();
    }

    private function anonimizarResidente(Residente $residente): void
    {
        $residente->familiares()->detach();

        // Fechas redondeadas al mes y textos libres fuera: con un diagnóstico poco común y
        // una fecha exacta se podría volver a identificar a la persona
        $residente->diagnosticos()->conAnulados()->update(['observaciones' => null, 'medico' => null, 'tratamiento' => null]);
        $residente->alergias()->conAnulados()->update(['observaciones' => null, 'reaccion' => null]);
        $residente->constantes()->conAnulados()->update(['observaciones' => null]);
        $residente->cuidadosDiarios()->conAnulados()->update(['observaciones' => null]);
        $residente->visitasMedicas()->conAnulados()->update(['medico' => null]);
        $residente->medicaciones()->conAnulados()->update(['observaciones' => null]);
        $residente->visitas()->conAnulados()->update(['nombre_visitante' => '—', 'documento' => '—', 'observaciones' => null]);

        $residente->forceFill([
            'nombre' => 'Residente',
            'apellidos' => 'anonimizado #'.$residente->id,
            'dni' => 'ANON-'.$residente->id,
            'nacimiento' => $residente->nacimiento?->startOfMonth(),
            'grupo_sanguineo' => null,
            'anonimizado_at' => now(),
        ])->saveQuietly();

        Log::registrar($residente, 'anonymised', ['politica' => 'historia_clinica']);
    }

    /** Empleados desactivados hace más de N años. */
    private function anonimizarCuentas(int $anios): int
    {
        $cuentas = User::where('activo', false)
            ->whereNull('anonimizado_at')
            ->where('updated_at', '<=', now()->subYears($anios))
            ->get();

        if ($this->simulacion) {
            return $cuentas->count();
        }

        foreach ($cuentas as $cuenta) {
            $cuenta->forceFill([
                'name' => 'Empleado anonimizado #'.$cuenta->id,
                'email' => 'anon-'.$cuenta->id.'@anonimizado.invalid',
                'password' => bcrypt(bin2hex(random_bytes(16))),
                'anonimizado_at' => now(),
            ])->saveQuietly();

            Log::registrar($cuenta, 'anonymised', ['politica' => 'cuentas_empleados']);
        }

        return $cuentas->count();
    }

    private function borrarFichajes(int $anios): int
    {
        $consulta = Fichaje::conAnulados()->where('entrada', '<=', now()->subYears($anios));

        if ($this->simulacion) {
            return $consulta->count();
        }

        $total = $consulta->count();
        // Las correcciones cuelgan del fichaje y se van con él (misma política)
        $consulta->get()->each(function (Fichaje $fichaje) {
            $fichaje->correcciones()->withoutGlobalScopes()->get()->each(fn ($c) => DB::table('fichaje_correcciones')->where('id', $c->id)->delete());
            DB::table('fichajes')->where('id', $fichaje->id)->delete();
        });

        return $total;
    }

    private function borrarAuditoria(int $anios): int
    {
        $consulta = Log::where('created_at', '<=', now()->subYears($anios));

        return $this->simulacion ? $consulta->count() : $consulta->delete();
    }

    private function borrarFamiliares(int $anios): int
    {
        $consulta = Familiar::doesntHave('residentes')->where('updated_at', '<=', now()->subYears($anios));

        return $this->simulacion ? $consulta->count() : $consulta->delete();
    }
}
