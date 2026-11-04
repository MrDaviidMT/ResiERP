<?php

namespace App\Console\Commands;

use App\Models\Log;
use App\Services\PoliticaConservacion;
use Illuminate\Console\Command;

// Aplica la política de conservación (ver config/conservacion.php). Programado una vez al mes.
class AplicarConservacion extends Command
{
    protected $signature = 'conservacion:aplicar
                            {--simular : No modifica nada, solo informa de lo que haría}
                            {--aplicar : Lo aplica aunque la configuración esté en modo simulación}';

    protected $description = 'Anonimiza o borra los datos personales cuyo plazo de conservación ha vencido';

    public function handle(): int
    {
        $simulacion = $this->option('simular') || (config('conservacion.simulacion') && ! $this->option('aplicar'));

        $this->info($simulacion
            ? 'Modo simulación: no se modifica nada, solo se informa.'
            : 'Aplicando la política de conservación: los datos vencidos se anonimizarán o se borrarán.');

        $resultado = (new PoliticaConservacion($simulacion))->aplicar();

        $this->table(['Acción', 'Registros'], collect($resultado)->map(
            fn (int $total, string $accion) => [str_replace('_', ' ', $accion), $total]
        )->values()->all());

        // La propia ejecución se audita, también cuando solo simula
        Log::registrarSistema($simulacion ? 'retention_dry_run' : 'retention_applied', [
            'anios' => config('conservacion.anios'),
            'resultado' => $resultado,
        ]);

        if ($simulacion && array_sum($resultado) > 0) {
            $this->warn('Para aplicarlo de verdad: CONSERVACION_SIMULACION=false, o ejecuta el comando con --aplicar.');
        }

        return self::SUCCESS;
    }
}
