<?php

namespace App\Http\Controllers;

use App\Models\Familiar;
use App\Models\Log;
use App\Models\Residente;
use App\Rules\DocumentoIdentidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FamiliarController extends Controller
{
    public function index(Request $request)
    {
        $query = Familiar::with('residentes');

        $filters = ['nombre', 'apellidos', 'dni', 'telefono', 'email'];
        foreach ($filters as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'LIKE', "%{$request->$field}%");
            }
        }

        $familiares = $query->orderBy('apellidos')->orderBy('nombre')->paginate(20);

        return view('familiares.lista', compact('familiares'));
    }

    public function create(Request $request)
    {
        $residentes = Residente::withoutTrashed()->with('habitacion')->orderBy('nombre')->orderBy('apellidos')->get();
        $preseleccionado = $request->integer('residente_id') ?: null;

        $vinculaciones = [];

        if ($oldVinculaciones = old('vinculaciones')) {
            foreach ($oldVinculaciones as $v) {
                $r = $residentes->find($v['residente_id']);
                $vinculaciones[] = [
                    'residente_id' => (int) $v['residente_id'],
                    'nombre_completo' => $r ? $r->nombre.' '.$r->apellidos : '?',
                    'habitacion_numero' => $r?->habitacion?->numero,
                    'parentesco' => $v['parentesco'] ?? 'Hijo/a',
                    'es_emergencia' => (bool) ($v['es_emergencia'] ?? false),
                    'autorizado' => (bool) ($v['autorizado'] ?? false),
                    'observaciones' => $v['observaciones'] ?? '',
                ];
            }
        }

        $preseleccionadoData = null;
        if ($preseleccionado) {
            $r = $residentes->find($preseleccionado);
            if ($r) {
                $preseleccionadoData = [
                    'id' => $r->id,
                    'nombre' => $r->nombre,
                    'apellidos' => $r->apellidos,
                    'dni' => $r->dni,
                    'habitacion_numero' => $r->habitacion?->numero,
                ];
            }
        }

        return view('familiares.create', compact('residentes', 'vinculaciones', 'preseleccionadoData'));
    }

    public function storeGeneral(Request $request)
    {
        $request->merge(['dni' => DocumentoIdentidad::normalizar((string) $request->input('dni'))]);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => ['required', 'string', 'max:20', new DocumentoIdentidad, 'unique:familiares,dni'],
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'vinculaciones' => 'required|array|min:1',
            'vinculaciones.*.residente_id' => 'required|exists:residentes,id',
            'vinculaciones.*.parentesco' => 'required|string|max:100',
            'vinculaciones.*.es_emergencia' => 'required|boolean',
            'vinculaciones.*.autorizado' => 'required|boolean',
            'vinculaciones.*.observaciones' => 'nullable|string',
        ]);

        $residente = Residente::where('dni', $data['dni'])->first();
        if ($residente && ($residente->nombre !== $data['nombre'] || $residente->apellidos !== $data['apellidos'])) {
            return back()->withInput()->withErrors([
                'dni' => 'Este DNI ya está registrado como residente con otro nombre. Los datos personales deben coincidir.',
            ]);
        }

        // El familiar y sus vinculaciones se crean juntos o no se crean
        $familiar = DB::transaction(function () use ($data) {
            $familiar = Familiar::create([
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'dni' => $data['dni'],
                'telefono' => $data['telefono'],
                'email' => $data['email'] ?? null,
                'direccion' => $data['direccion'] ?? null,
            ]);

            $familiar->residentes()->attach($this->datosPivote($data['vinculaciones']));

            return $familiar;
        });

        return redirect()->route('familiares.lista')
            ->with('success', 'Familiar añadido correctamente.');
    }

    public function show(Familiar $familiar)
    {
        $familiar->load('residentes');

        return view('familiares.show', compact('familiar'));
    }

    public function edit(Familiar $familiar)
    {
        $familiar->load('residentes');
        $residentes = Residente::withoutTrashed()->with('habitacion')->orderBy('nombre')->orderBy('apellidos')->get();

        if ($oldVinculaciones = old('vinculaciones')) {
            $vinculaciones = [];
            foreach ($oldVinculaciones as $v) {
                $r = $residentes->find($v['residente_id']);
                $vinculaciones[] = [
                    'residente_id' => (int) $v['residente_id'],
                    'nombre_completo' => $r ? $r->nombre.' '.$r->apellidos : '?',
                    'habitacion_numero' => $r?->habitacion?->numero,
                    'parentesco' => $v['parentesco'] ?? 'Hijo/a',
                    'es_emergencia' => (bool) ($v['es_emergencia'] ?? false),
                    'autorizado' => (bool) ($v['autorizado'] ?? false),
                    'observaciones' => $v['observaciones'] ?? '',
                ];
            }
        } else {
            $vinculaciones = $familiar->residentes->map(fn ($r) => [
                'residente_id' => $r->id,
                'nombre_completo' => $r->nombre.' '.$r->apellidos,
                'habitacion_numero' => $r->habitacion?->numero,
                'parentesco' => $r->pivot->parentesco,
                'es_emergencia' => (bool) $r->pivot->es_emergencia,
                'autorizado' => (bool) $r->pivot->autorizado,
                'observaciones' => $r->pivot->observaciones ?? '',
            ])->values()->toArray();
        }

        return view('familiares.edit', compact('familiar', 'residentes', 'vinculaciones'));
    }

    public function porResidente(Residente $residente)
    {
        $residente->load('familiares.residentes');
        $todosFamiliares = Familiar::orderBy('apellidos')->orderBy('nombre')->get();

        return view('residentes.familiares.index', compact('residente', 'todosFamiliares'));
    }

    public function store(Request $request, Residente $residente)
    {
        $request->merge(['dni' => DocumentoIdentidad::normalizar((string) $request->input('dni'))]);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => ['required', 'string', 'max:20', new DocumentoIdentidad, 'unique:familiares,dni'],
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'vinculaciones' => 'required|array|min:1',
            'vinculaciones.*.residente_id' => 'required|exists:residentes,id',
            'vinculaciones.*.parentesco' => 'required|string|max:100',
            'vinculaciones.*.es_emergencia' => 'required|boolean',
            'vinculaciones.*.autorizado' => 'required|boolean',
            'vinculaciones.*.observaciones' => 'nullable|string',
        ]);

        $residenteExistente = Residente::where('dni', $data['dni'])->first();
        if ($residenteExistente && ($residenteExistente->nombre !== $data['nombre'] || $residenteExistente->apellidos !== $data['apellidos'])) {
            return back()->withInput()->withErrors([
                'dni' => 'Este DNI ya está registrado como residente con otro nombre. Los datos personales deben coincidir.',
            ]);
        }

        // El familiar y sus vinculaciones se crean juntos o no se crean
        $familiar = DB::transaction(function () use ($data) {
            $familiar = Familiar::create([
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'dni' => $data['dni'],
                'telefono' => $data['telefono'],
                'email' => $data['email'] ?? null,
                'direccion' => $data['direccion'] ?? null,
            ]);

            $familiar->residentes()->attach($this->datosPivote($data['vinculaciones']));

            return $familiar;
        });

        return redirect()->route('familiares.index', $residente)
            ->with('success', 'Familiar añadido correctamente.');
    }

    public function update(Request $request, Familiar $familiar)
    {
        $request->merge(['dni' => DocumentoIdentidad::normalizar((string) $request->input('dni'))]);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => ['required', 'string', 'max:20', new DocumentoIdentidad, Rule::unique('familiares', 'dni')->ignore($familiar->id)],
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'vinculaciones' => 'nullable|array',
            'vinculaciones.*.residente_id' => 'required|exists:residentes,id',
            'vinculaciones.*.parentesco' => 'required|string|max:100',
            'vinculaciones.*.es_emergencia' => 'required|boolean',
            'vinculaciones.*.autorizado' => 'required|boolean',
            'vinculaciones.*.observaciones' => 'nullable|string',
        ]);

        $residente = Residente::where('dni', $data['dni'])->first();
        if ($residente && ($residente->nombre !== $data['nombre'] || $residente->apellidos !== $data['apellidos'])) {
            return back()->withInput()->withErrors([
                'dni' => 'Este DNI ya está registrado como residente con otro nombre. Los datos personales deben coincidir.',
            ]);
        }

        $familiar->update([
            'nombre' => $data['nombre'],
            'apellidos' => $data['apellidos'],
            'dni' => $data['dni'],
            'telefono' => $data['telefono'],
            'email' => $data['email'] ?? null,
            'direccion' => $data['direccion'] ?? null,
        ]);

        $pivote = $this->datosPivote($data['vinculaciones'] ?? []);
        $familiar->residentes()->sync($pivote);

        // Queda constancia de con qué residentes queda vinculado y con qué autorización
        Log::registrar($familiar, 'links_synced', [
            'vinculos' => collect($pivote)->map(fn (array $v, int $residenteId) => ['residente_id' => $residenteId] + $v)->values()->all(),
        ]);

        return redirect()->route('familiares.lista')
            ->with('success', 'Familiar actualizado correctamente.');
    }

    public function vincularExistente(Request $request, Residente $residente)
    {
        $data = $request->validate([
            'familiar_id' => [
                'required',
                'exists:familiares,id',
                Rule::unique('familiar_residente', 'familiar_id')->where(function ($q) use ($residente) {
                    $q->where('residente_id', $residente->id);
                }),
            ],
            'parentesco' => 'required|string|max:100',
            'es_emergencia' => 'boolean',
            'autorizado' => 'boolean',
            'observaciones' => 'nullable|string',
        ]);

        $vinculo = [
            'parentesco' => $data['parentesco'],
            // Fail-closed: una casilla desmarcada no se envía, y eso debe significar "no"
            'es_emergencia' => $request->boolean('es_emergencia'),
            'autorizado' => $request->boolean('autorizado'),
            'observaciones' => $data['observaciones'] ?? null,
        ];

        $residente->familiares()->attach($data['familiar_id'], $vinculo);

        // Las tablas intermedias no disparan el Observer; quién autoriza a un familiar debe constar
        Log::registrar(Familiar::find($data['familiar_id']), 'linked', ['residente_id' => $residente->id] + $vinculo);

        return redirect()->route('familiares.index', $residente)
            ->with('success', 'Familiar vinculado correctamente.');
    }

    public function destroy(Familiar $familiar)
    {
        if ($familiar->residentes()->count() > 0) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar este familiar porque sigue vinculado a uno o varios residentes. Para desvincularlo, edita el familiar y elimina la vinculación desde la sección "Vincular a residentes".');
        }

        $familiar->delete();

        return redirect()->route('familiares.lista')
            ->with('success', 'Familiar eliminado.');
    }

    /**
     * Datos de la tabla intermedia familiar_residente a partir del formulario.
     * Los indicadores llegan validados como obligatorios: nunca se suponen.
     *
     * @return array<int, array<string, mixed>>
     */
    private function datosPivote(array $vinculaciones): array
    {
        $pivote = [];
        foreach ($vinculaciones as $v) {
            $pivote[$v['residente_id']] = [
                'parentesco' => $v['parentesco'],
                'es_emergencia' => (bool) $v['es_emergencia'],
                'autorizado' => (bool) $v['autorizado'],
                'observaciones' => $v['observaciones'] ?? null,
            ];
        }

        return $pivote;
    }
}
