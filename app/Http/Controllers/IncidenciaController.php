<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\MotivoRequest;
use App\Models\Habitacion;
use App\Models\Incidencia;
use App\Models\Residente;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IncidenciaController extends Controller
{
    public function index(Request $request)
    {
        $categoria = $request->input('categoria');
        $estado = $request->input('estado');
        $prioridad = $request->input('prioridad');

        $query = Incidencia::with(['habitacion', 'residente']);

        // D9: las incidencias de tipo "personal" solo las ve dirección y quien las registró
        if (! Gate::allows(Permission::IncidenciasVerPersonal)) {
            $query->where(fn ($q) => $q->where('categoria', '!=', 'personal')->orWhere('user_id', $request->user()->id));
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $categoria);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $estado);
        }
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $prioridad);
        }
        if ($request->filled('registrada_desde')) {
            $query->whereDate('created_at', '>=', $request->registrada_desde);
        }
        if ($request->filled('registrada_hasta')) {
            $query->whereDate('created_at', '<=', $request->registrada_hasta);
        }
        if ($request->filled('resuelta_desde')) {
            $query->whereDate('fecha_resolucion', '>=', $request->resuelta_desde);
        }
        if ($request->filled('resuelta_hasta')) {
            $query->whereDate('fecha_resolucion', '<=', $request->resuelta_hasta);
        }

        // CASE es SQL estándar (FIELD() solo existe en MySQL y rompía los tests con SQLite)
        $incidencias = $query->orderByRaw("CASE estado WHEN 'pendiente' THEN 1 WHEN 'en_proceso' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE prioridad WHEN 'urgente' THEN 1 WHEN 'grave' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $habitaciones = Habitacion::orderBy('numero')->get();
        // D11: el selector de residentes solo se carga para quien puede ver residentes
        $residentes = $request->user()->can(Permission::ResidentesVer)
            ? Residente::withoutTrashed()->with('habitacion')->orderBy('nombre')->get()
            : collect();

        // Anuladas, con la misma regla de visibilidad que las vigentes (las de tipo "personal", solo dirección y su autor)
        $anuladas = Incidencia::soloAnulados()
            ->when(! Gate::allows(Permission::IncidenciasVerPersonal), fn ($q) => $q->where(fn ($q) => $q->where('categoria', '!=', 'personal')->orWhere('user_id', $request->user()->id)))
            ->with('anuladoPor')->latest('anulado_at')->get()
            ->map(fn (Incidencia $i) => ['resumen' => $i->titulo.' ('.$i->categoria.')', 'anulado_at' => $i->anulado_at, 'por' => $i->anuladoPor?->name, 'motivo' => $i->motivo_anulacion]);

        return view('incidencias.index', compact('incidencias', 'habitaciones', 'residentes', 'anuladas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'categoria' => 'required|in:sanitaria,infraestructura,limpieza,personal,otros',
            'titulo' => 'required|string|max:150',
            'prioridad' => 'required|in:leve,grave,urgente',
            'descripcion' => 'nullable|string',
            'habitacion_id' => 'nullable|exists:habitaciones,id',
            'residente_id' => ['nullable', Rule::exists('residentes', 'id')->whereNull('deleted_at')],
        ]);

        if (! $request->user()->can(Permission::ResidentesVer)) {
            unset($data['residente_id']);
        }

        // El autor no es asignable desde el formulario: se toma del usuario autenticado
        $incidencia = new Incidencia($data);
        $incidencia->user_id = $request->user()->id;
        $incidencia->save();

        if ($request->filled('habitacion_id') && Incidencia::bloquea($data['categoria'], $data['prioridad'])) {
            $habitacion = Habitacion::withCount('residentes')->findOrFail($request->habitacion_id);

            if ($habitacion->residentes_count > 0) {
                return redirect()->back()->with('success', 'Incidencia registrada. La habitación no se ha puesto en mantenimiento porque tiene residentes asignados.');
            }

            $habitacion->update(['estado' => 'mantenimiento']);
        }

        return redirect()->back()->with('success', 'Incidencia unificada registrada y notificada con éxito.');
    }

    public function show(Incidencia $incidencia)
    {
        $incidencia->load(['habitacion', 'residente']);

        return view('incidencias.show', compact('incidencia'));
    }

    public function edit(Incidencia $incidencia)
    {
        $incidencia->load(['habitacion', 'residente']);

        $habitaciones = Habitacion::orderBy('numero')->get();
        // D11: el selector de residentes solo se carga para quien puede ver residentes
        $residentes = auth()->user()->can(Permission::ResidentesVer)
            ? Residente::withoutTrashed()->with('habitacion')->orderBy('nombre')->get()
            : collect();

        return view('incidencias.edit', compact('incidencia', 'habitaciones', 'residentes'));
    }

    public function update(Request $request, Incidencia $incidencia)
    {

        $data = $request->validate([
            'categoria' => 'required|in:sanitaria,infraestructura,limpieza,personal,otros',
            'titulo' => 'required|string|max:150',
            'prioridad' => 'required|in:leve,grave,urgente',
            'descripcion' => 'nullable|string',
            'habitacion_id' => 'nullable|exists:habitaciones,id',
            'residente_id' => ['nullable', Rule::exists('residentes', 'id')->whereNull('deleted_at')],
        ]);

        // Además de poder atender la incidencia actual, hay que poder atender la categoría a la que se mueve
        if (! Gate::allows(Permission::atenderIncidencia($data['categoria']))) {
            return back()->withInput()->with('error', 'No tienes permiso para asignar esta categoría de incidencia.');
        }

        if (! $request->user()->can(Permission::ResidentesVer)) {
            unset($data['residente_id']);
        }

        $incidencia->update($data);

        return redirect()->route('incidencias.show', $incidencia)->with('success', 'Incidencia actualizada correctamente.');
    }

    /** La incidencia se conserva anulada; si mantenía la habitación bloqueada, se libera igual que al resolverla. */
    public function anular(MotivoRequest $request, Incidencia $incidencia)
    {
        $incidencia->anular($request->validated('motivo'));
        $this->liberarHabitacionSiProcede($incidencia);

        return redirect()->route('incidencias.index')->with('success', 'Incidencia anulada.');
    }

    public function actualizarEstado(Incidencia $incidencia, string $estado)
    {
        $nuevoEstado = $estado;

        if (! $incidencia->puedeCambiarA($nuevoEstado)) {
            return back()->with('error', 'No se puede pasar la incidencia de este estado al solicitado.');
        }

        $datos = ['estado' => $nuevoEstado];

        if ($nuevoEstado === 'resuelta') {
            $datos['fecha_resolucion'] = Carbon::now();
        } else {
            $datos['fecha_resolucion'] = null;
        }

        $incidencia->update($datos);
        $this->liberarHabitacionSiProcede($incidencia);

        return redirect()->back()->with('success', 'Estado de la incidencia actualizado correctamente.');
    }

    private function liberarHabitacionSiProcede(Incidencia $incidencia): void
    {
        if ($incidencia->habitacion_id) {
            Habitacion::find($incidencia->habitacion_id)?->liberarSiNoHayIncidenciasBloqueantes();
        }
    }
}
