<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\MotivoRequest;
use App\Models\Actividad;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActividadController extends Controller
{
    public function index(Request $request)
    {
        $query = Actividad::with('user', 'residentes');

        if ($request->filled('titulo')) {
            $query->where('titulo', 'like', '%'.$request->titulo.'%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->hasta);
        }

        $actividades = $query->latest()->paginate(20);

        $usuarios = User::withoutRole(Role::Superadmin)->orderBy('name')->get();

        $anuladas = Actividad::soloAnulados()->with('anuladoPor')->latest('anulado_at')->get()

            ->map(fn (Actividad $a) => ['resumen' => $a->titulo.' ('.$a->fecha?->format('d/m/Y').')', 'anulado_at' => $a->anulado_at, 'por' => $a->anuladoPor?->name, 'motivo' => $a->motivo_anulacion]);

        return view('actividades.index', compact('actividades', 'usuarios', 'anuladas'));
    }

    public function create()
    {
        $residentes = Residente::withoutTrashed()->with('habitacion')->orderBy('apellidos')->get();

        return view('actividades.create', compact('residentes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha' => 'required|date',
            'hora' => 'nullable|date_format:H:i',
            'residentes' => 'nullable|array',
            'residentes.*' => [Rule::exists('residentes', 'id')->whereNull('deleted_at')],
        ]);

        $actividad = Actividad::create([
            'user_id' => auth()->id(),
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha' => $data['fecha'],
            'hora' => $data['hora'] ?? null,
        ]);

        if (! empty($data['residentes'])) {
            $actividad->residentes()->attach($data['residentes']);
        }

        return redirect()->route('actividades.index')->with('success', 'Actividad creada correctamente.');
    }

    public function show(Actividad $actividad)
    {
        $actividad->load('user', 'residentes');

        return view('actividades.show', compact('actividad'));
    }

    public function edit(Actividad $actividad)
    {
        $residentes = Residente::withoutTrashed()->with('habitacion')->orderBy('apellidos')->get();

        return view('actividades.edit', compact('actividad', 'residentes'));
    }

    public function update(Request $request, Actividad $actividad)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha' => 'required|date',
            'hora' => 'nullable|date_format:H:i',
            'residentes' => 'nullable|array',
            'residentes.*' => [Rule::exists('residentes', 'id')->whereNull('deleted_at')],
        ]);

        $actividad->update([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha' => $data['fecha'],
            'hora' => $data['hora'] ?? null,
        ]);

        $actividad->residentes()->sync($data['residentes'] ?? []);

        return redirect()->route('actividades.index')->with('success', 'Actividad actualizada correctamente.');
    }

    /** La actividad se conserva anulada (con sus participantes), con motivo. */
    public function anular(MotivoRequest $request, Actividad $actividad)
    {
        $actividad->anular($request->validated('motivo'));

        return redirect()->route('actividades.index')->with('success', 'Actividad anulada.');
    }
}
