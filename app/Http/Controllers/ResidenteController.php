<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Familiar;
use App\Models\Habitacion;
use App\Models\Log;
use App\Models\Residente;
use App\Rules\DocumentoIdentidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResidenteController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->input('buscar');
        $habitacion_id = $request->input('habitacion_id');
        $grupo_sanguineo = $request->input('grupo_sanguineo');
        $estado = $request->input('estado', 'activos');

        $ingresoDesde = $request->input('ingreso_desde');
        $ingresoHasta = $request->input('ingreso_hasta');

        $nacimientoDesde = $request->input('nac_desde');
        $nacimientoHasta = $request->input('nac_hasta');

        if ($estado === 'archivados') {
            $query = Residente::onlyTrashed()->with('habitacion');
        } else {
            $query = Residente::with('habitacion');
        }

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'LIKE', "%{$buscar}%")
                    ->orWhere('apellidos', 'LIKE', "%{$buscar}%")
                    ->orWhere('dni', 'LIKE', "%{$buscar}%");
            });
        }

        if ($request->filled('habitacion_id')) {
            $query->where('habitacion_id', $habitacion_id);
        }

        // Filtrar por grupo sanguíneo revelaría el dato a quien no puede verlo (L1)
        if ($request->filled('grupo_sanguineo') && $request->user()->can(Permission::ResidentesVerSalud)) {
            $query->where('grupo_sanguineo', $grupo_sanguineo);
        }

        if (! empty($ingresoDesde)) {
            $query->whereDate('ingreso', '>=', $ingresoDesde);
        }
        if (! empty($ingresoHasta)) {
            $query->whereDate('ingreso', '<=', $ingresoHasta);
        }

        if (! empty($nacimientoDesde)) {
            $query->whereDate('nacimiento', '>=', $nacimientoDesde);
        }
        if (! empty($nacimientoHasta)) {
            $query->whereDate('nacimiento', '<=', $nacimientoHasta);
        }

        $residentes = $query->paginate(20);
        $habitaciones = Habitacion::orderBy('numero')->get();

        return view('residentes.index', compact(
            'residentes',
            'habitaciones',
            'nacimientoDesde',
            'nacimientoHasta'
        ));
    }

    public function create()
    {
        $habitaciones = Habitacion::where('estado', '!=', 'mantenimiento')
            ->withCount('residentes')
            ->get()
            ->filter->tienePlazasLibres();

        return view('residentes.create', compact('habitaciones'));
    }

    public function store(Request $request)
    {
        // D17: mismo documento escrito de dos formas = mismo documento
        $request->merge(['dni' => DocumentoIdentidad::normalizar((string) $request->input('dni'))]);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => ['required', 'string', 'max:20', new DocumentoIdentidad, 'unique:residentes,dni'],
            'nacimiento' => 'required|date|before:today',
            'ingreso' => 'required|date|after:nacimiento',
            'habitacion_id' => 'required|exists:habitaciones,id',
            'grupo_sanguineo' => ['nullable', Rule::in(Residente::GRUPOS_SANGUINEOS)],
            'dependencia' => 'nullable|string|in:ninguno,leve,moderado,grave,gran_dependiente',
        ]);

        $familiar = Familiar::where('dni', $data['dni'])->first();
        if ($familiar && ($familiar->nombre !== $data['nombre'] || $familiar->apellidos !== $data['apellidos'])) {
            return back()->withInput()->withErrors([
                'dni' => 'Este DNI ya está registrado como familiar con otro nombre. Los datos personales deben coincidir.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            $habitacion = Habitacion::lockForUpdate()
                ->withCount('residentes')
                ->findOrFail($data['habitacion_id']);

            if (! $habitacion->tienePlazasLibres()) {
                return back()->withInput()->withErrors([
                    'habitacion_id' => 'Esta habitación ya está completa.',
                ]);
            }

            Residente::create($data);
            $habitacion->sincronizarEstado();

            return redirect()->route('residentes.index')
                ->with('success', 'Residente dado de alta correctamente.');
        });
    }

    public function edit($id)
    {
        // D10: un residente archivado no se edita (asignarle habitación ocuparía una cama sin contar)
        $residente = Residente::findOrFail($id);

        $habitaciones = Habitacion::where('estado', '!=', 'mantenimiento')
            ->withCount('residentes')
            ->get()
            ->filter(fn ($h) => $h->tienePlazasLibres() || $h->id === $residente->habitacion_id
            );

        return view('residentes.edit', compact('residente', 'habitaciones'));
    }

    public function update(Request $request, $id)
    {
        $residente = Residente::findOrFail($id);
        $habitacionViejaId = $residente->habitacion_id;

        // D17: mismo documento escrito de dos formas = mismo documento
        $request->merge(['dni' => DocumentoIdentidad::normalizar((string) $request->input('dni'))]);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => ['required', 'string', 'max:20', new DocumentoIdentidad, Rule::unique('residentes', 'dni')->ignore($residente->id)],
            'nacimiento' => 'required|date|before:today',
            'ingreso' => 'required|date|after:nacimiento',
            // D12: registrar una defunción da de baja al residente, así que exige el permiso de baja
            'fecha_defuncion' => [
                'nullable', 'date', 'after:nacimiento', 'before_or_equal:today',
                Rule::prohibitedIf(fn () => ! $request->user()->can(Permission::ResidentesBaja)),
            ],
            'habitacion_id' => 'nullable|exists:habitaciones,id',
            'grupo_sanguineo' => ['nullable', Rule::in(Residente::GRUPOS_SANGUINEOS)],
            'dependencia' => 'nullable|string|in:ninguno,leve,moderado,grave,gran_dependiente',
        ]);

        $familiar = Familiar::where('dni', $data['dni'])->first();
        if ($familiar && ($familiar->nombre !== $data['nombre'] || $familiar->apellidos !== $data['apellidos'])) {
            return back()->withInput()->withErrors([
                'dni' => 'Este DNI ya está registrado como familiar con otro nombre. Los datos personales deben coincidir.',
            ]);
        }

        return DB::transaction(function () use ($data, $residente, $habitacionViejaId) {

            if ($data['habitacion_id'] != $habitacionViejaId) {
                if ($data['habitacion_id']) {
                    $nuevaHabitacion = Habitacion::lockForUpdate()
                        ->withCount('residentes')
                        ->findOrFail($data['habitacion_id']);

                    if (! $nuevaHabitacion->tienePlazasLibres()) {
                        return back()->withInput()->withErrors([
                            'habitacion_id' => 'La habitación de destino ya está completa.',
                        ]);
                    }
                }
            }

            $residente->update($data);

            // D12: un residente fallecido deja libre su cama y pasa al histórico
            if (! empty($data['fecha_defuncion'])) {
                $residente->update(['habitacion_id' => null]);
                $residente->delete();

                if ($habitacionViejaId) {
                    Habitacion::lockForUpdate()->withCount('residentes')->findOrFail($habitacionViejaId)->sincronizarEstado();
                }

                return redirect()->route('residentes.index')
                    ->with('success', 'Defunción registrada. El residente pasa al histórico y su plaza queda libre.');
            }

            if ($data['habitacion_id'] != $habitacionViejaId) {
                if ($habitacionViejaId) {
                    Habitacion::lockForUpdate()
                        ->withCount('residentes')
                        ->findOrFail($habitacionViejaId)
                        ->sincronizarEstado();
                }

                if ($data['habitacion_id']) {
                    Habitacion::lockForUpdate()
                        ->withCount('residentes')
                        ->findOrFail($data['habitacion_id'])
                        ->sincronizarEstado();
                }
            }

            return redirect()->route('residentes.index')
                ->with('success', 'Residente actualizado correctamente.');
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $residente = Residente::findOrFail($id);
            $habitacionId = $residente->habitacion_id;

            $residente->update([
                'habitacion_id' => null,
            ]);
            $residente->delete();

            if ($habitacionId) {
                Habitacion::lockForUpdate()
                    ->withCount('residentes')
                    ->findOrFail($habitacionId)
                    ->sincronizarEstado();
            }

            return redirect()->route('residentes.index')
                ->with('success', 'El residente ha sido dado de baja correctamente.');
        });
    }

    public function restaurar($id)
    {
        $residente = Residente::onlyTrashed()->findOrFail($id);

        // La baja (destroy) ya libera la habitación: se reincorpora siempre sin habitación asignada
        $residente->habitacion_id = null;
        $residente->restore();

        return redirect()->route('residentes.index')
            ->with('success', 'Residente reincorporado. Recuerda asignarle una habitación desde su perfil.');
    }

    public function show($id)
    {
        $residente = Residente::with([
            'habitacion',
            'incidencias' => fn ($q) => $q->latest()->limit(10),
            'diagnosticos',
            'alergias',
            'medicaciones',
            'familiares',
            'dieta',
            'constantes' => fn ($q) => $q->latest('fecha_registro')->limit(10),
            'cuidadosDiarios' => fn ($q) => $q->latest()->limit(10),
            'visitasMedicas' => fn ($q) => $q->latest('fecha_visita')->limit(10),
        ])
            ->withTrashed()
            ->findOrFail($id);

        // El resumen enseña medicación y diagnósticos: es una consulta clínica como las de sus
        // propias pantallas y se audita igual. Quien no puede verlos no ve nada clínico aquí.
        if (auth()->user()->canAny([Permission::MedicacionVer, Permission::DiagnosticosVer])) {
            Log::registrar($residente, 'viewed', ['pantalla' => 'resumen']);
        }

        $proximaVisita = $residente->visitasMedicas()
            ->where('fecha_visita', '>=', now())
            ->oldest('fecha_visita')
            ->first();

        return view('residentes.perfil', compact('residente', 'proximaVisita'));
    }
}
