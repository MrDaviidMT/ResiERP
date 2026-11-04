<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = Log::with('user');

        if ($request->filled('loggable_type')) {
            $query->where('loggable_type', 'App\\Models\\'.$request->loggable_type);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        $logs = $query->latest()->paginate(50);

        $modelos = [
            'Residente', 'Habitacion', 'Familiar', 'Incidencia',
            'Medicacion', 'Diagnostico', 'ConstanteVital',
            'VisitaMedica', 'Dieta', 'Menu', 'Visita',
            'CuidadoDiario', 'Informe', 'Actividad',
            'User', 'Fichaje', 'Alergia',
        ];

        $usuarios = User::orderBy('name')->get();

        return view('logs.index', compact('logs', 'modelos', 'usuarios'));
    }
}
