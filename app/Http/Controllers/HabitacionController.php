<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use App\Models\Incidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HabitacionController extends Controller
{
    public function index()
    {
        $habitaciones = Habitacion::withCount('residentes')->with('residentes')->paginate(20);
        $todas = Habitacion::withCount('residentes')->with('residentes')->get();

        return view('habitaciones.index', compact('habitaciones', 'todas'));
    }

    public function create()
    {
        return view('habitaciones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero' => 'required|string|max:10|unique:habitaciones,numero',
            'tipo' => 'required|in:individual,doble',
            'observaciones' => 'nullable|string',
        ]);

        $data['estado'] = 'libre';
        Habitacion::create($data);

        return redirect()->route('habitaciones.index')->with('success', 'Habitación creada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'tipo' => 'required|in:individual,doble',
            'observaciones' => 'nullable|string',
        ]);

        // Bloqueo de fila, igual que al asignar habitación a un residente (evita la condición de carrera)
        return DB::transaction(function () use ($data, $id) {
            $habitacion = Habitacion::lockForUpdate()->withCount('residentes')->findOrFail($id);

            $nuevaCapacidad = $data['tipo'] === 'doble' ? 2 : 1;
            if ($habitacion->residentes_count > $nuevaCapacidad) {
                return back()->withErrors([
                    'error' => "No se puede cambiar a {$data['tipo']}: la habitación tiene {$habitacion->residentes_count} residente(s).",
                ]);
            }

            $habitacion->update($data);
            $habitacion->sincronizarEstado();

            return redirect()->route('habitaciones.index')->with('success', 'Habitación actualizada correctamente.');
        });
    }

    public function toggleMantenimiento($id)
    {
        return DB::transaction(function () use ($id) {
            $habitacion = Habitacion::lockForUpdate()->withCount('residentes')->findOrFail($id);
            if ($habitacion->estado === 'mantenimiento') {
                $habitacion->forceFill([
                    'estado' => $habitacion->residentes_count > 0 ? 'ocupada' : 'libre',
                    'mantenimiento_manual' => false,
                ])->save();

                return back()->with('success', 'La habitación vuelve a estar disponible.');
            }
            if ($habitacion->residentes_count > 0) {
                return back()->withErrors(['error' => 'No puedes poner en mantenimiento una habitación que tiene residentes dentro. ¡Muévelos primero!']);
            }
            // A mano: no se libera sola al resolver incidencias
            $habitacion->forceFill(['estado' => 'mantenimiento', 'mantenimiento_manual' => true])->save();

            return back()->with('success', 'Habitación puesta en mantenimiento. Se ha bloqueado para nuevas altas.');
        });
    }

    public function destroy($id)
    {
        $habitacion = Habitacion::withCount('residentes')->findOrFail($id);
        if ($habitacion->residentes_count > 0) {
            return back()->withErrors([
                'error' => "No puedes eliminar la Habitación {$habitacion->numero} porque tiene {$habitacion->residentes_count} residente(s) asignado(s). Traslada a los residentes antes de borrarla.",
            ]);
        }
        // Una habitación con historial de incidencias (también anuladas) no se borra
        if (Incidencia::conAnulados()->where('habitacion_id', $habitacion->id)->exists()) {
            return back()->withErrors([
                'error' => "No puedes eliminar la Habitación {$habitacion->numero} porque tiene historial de incidencias. Si ya no se usa, ponla en mantenimiento.",
            ]);
        }

        $habitacion->delete();

        return redirect()->route('habitaciones.index')->with('success', 'Habitación eliminada correctamente del sistema.');
    }
}
