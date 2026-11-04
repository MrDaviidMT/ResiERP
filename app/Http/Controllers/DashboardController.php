<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Alergia;
use App\Models\Familiar;
use App\Models\Habitacion;
use App\Models\Incidencia;
use App\Models\Log;
use App\Models\Menu;
use App\Models\Residente;
use App\Models\Visita;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $totales = Habitacion::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'libre' THEN 1 ELSE 0 END) as libres,
            SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
            SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) as mantenimiento
        ")->first();

        $habitaciones = Habitacion::all();
        $totalCamas = $habitaciones->sum(function ($hab) {
            return strtolower($hab->tipo) === 'doble' ? 2 : 1;
        });

        $residentesActivos = Residente::whereNotNull('habitacion_id')->count();

        $porcentajeOcupacion = $totalCamas > 0
            ? round(($residentesActivos / $totalCamas) * 100, 1)
            : 0;

        $conAlergias = Alergia::where('activa', true)
            ->distinct('residente_id')
            ->count('residente_id');

        $bajasMes = Residente::onlyTrashed()
            ->where('deleted_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        $incidenciasActivas = Incidencia::whereIn('estado', ['pendiente', 'en_proceso'])->count();
        $incidenciasUrgentes = Incidencia::where('prioridad', 'urgente')->where('estado', '!=', 'resuelta')->count();
        $nuevosIngresos = Residente::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $menuHoy = auth()->user()->can(Permission::MenusVer) ? Menu::whereDate('fecha', Carbon::today())->first() : null;
        $visitasHoy = Visita::whereDate('entrada', Carbon::today())->count();
        $cumpleanosMes = Residente::whereNotNull('habitacion_id')
            ->whereMonth('nacimiento', Carbon::now()->month)
            ->count();
        $sinHabitacion = Residente::withoutTrashed()->whereNull('habitacion_id')->count();
        $familiaresSinVinculo = Familiar::doesntHave('residentes')->count();
        $logsHoy = Log::whereDate('created_at', Carbon::today())->count();

        return view('index', [
            'habitacionesLibres' => $totales->libres ?? 0,
            'habitacionesOcupadas' => $totales->ocupadas ?? 0,
            'habitacionesMantenimiento' => $totales->mantenimiento ?? 0,
            'totalCamas' => $totalCamas,
            'residentesActivos' => $residentesActivos,
            'porcentajeOcupacion' => $porcentajeOcupacion,
            'conAlergias' => $conAlergias,
            'bajasMes' => $bajasMes,
            'incidenciasActivas' => $incidenciasActivas,
            'incidenciasUrgentes' => $incidenciasUrgentes,
            'nuevosIngresos' => $nuevosIngresos,
            'menuHoy' => $menuHoy,
            'visitasHoy' => $visitasHoy,
            'cumpleanosMes' => $cumpleanosMes,
            'sinHabitacion' => $sinHabitacion,
            'familiaresSinVinculo' => $familiaresSinVinculo,
            'logsHoy' => $logsHoy,
        ]);
    }
}
