<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Requests\MotivoRequest;
use App\Models\Informe;
use App\Models\Log;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Smalot\PdfParser\Parser;

class InformeController extends Controller
{
    public function index(Request $request)
    {
        // D2: el listado solo muestra los tipos que el usuario puede leer
        $query = Informe::with('user', 'residentes')->visiblesPara($request->user());

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
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

        $informes = $query->latest()->paginate(20);

        $tipos = Informe::tiposVisiblesPara($request->user());
        $usuarios = User::withoutRole(Role::Superadmin)->orderBy('name')->get();

        $anulados = Informe::soloAnulados()->visiblesPara($request->user())->with('anuladoPor')->latest('anulado_at')->get()
            ->map(fn (Informe $i) => [
                'resumen' => $i->titulo.' ('.(Informe::TIPOS[$i->tipo] ?? $i->tipo).')',
                'anulado_at' => $i->anulado_at,
                'por' => $i->anuladoPor?->name,
                'motivo' => $i->motivo_anulacion,
            ]);

        return view('informes.index', compact('informes', 'tipos', 'usuarios', 'anulados'));
    }

    public function create(Request $request)
    {
        $residentes = Residente::withoutTrashed()->with('habitacion')->orderBy('apellidos')->get();
        $tipos = Informe::tiposCreablesPor(auth()->user());

        $preseleccionado = $request->integer('residente_id');

        return view('informes.create', compact('residentes', 'tipos', 'preseleccionado'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => ['required', 'string', Rule::in(array_keys(Informe::TIPOS))],
            'contenido' => 'required|string',
            'residentes' => 'nullable|array',
            'residentes.*' => [Rule::exists('residentes', 'id')->whereNull('deleted_at')],
            'pdf' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:20480',
                function ($attribute, $value, $fail) {
                    if (! $this->esPdfValido($value)) {
                        $fail('El archivo no es un PDF válido (la estructura interna no corresponde a un PDF auténtico).');
                    }
                },
            ],
        ]);

        if (! Gate::allows(Permission::crearInforme($data['tipo']))) {
            return back()->withInput()->with('error', 'No tienes permiso para crear informes de este tipo.');
        }

        $informe = Informe::create([
            'titulo' => $data['titulo'],
            'tipo' => $data['tipo'],
            'contenido' => $data['contenido'],
        ]);

        if (! empty($data['residentes'])) {
            $informe->residentes()->attach($data['residentes']);
        }

        if ($request->hasFile('pdf')) {
            $file = $request->file('pdf');
            $path = $file->storeAs('informes', $file->hashName(), 'local');
            $informe->update(['pdf_path' => $path]);
        }

        return redirect()->route('informes.index')->with('success', 'Informe creado correctamente.');
    }

    public function show(Informe $informe)
    {
        $informe->load('user', 'residentes');
        Log::registrar($informe, 'viewed', ['pantalla' => 'informe']);

        return view('informes.show', compact('informe'));
    }

    public function edit(Informe $informe)
    {
        $residentes = Residente::withoutTrashed()->with('habitacion')->orderBy('apellidos')->get();
        $tipos = Informe::tiposCreablesPor(auth()->user());

        return view('informes.edit', compact('informe', 'residentes', 'tipos'));
    }

    public function update(Request $request, Informe $informe)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => ['required', 'string', Rule::in(array_keys(Informe::TIPOS))],
            'contenido' => 'required|string',
            'residentes' => 'nullable|array',
            'residentes.*' => [Rule::exists('residentes', 'id')->whereNull('deleted_at')],
            'pdf' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:20480',
                function ($attribute, $value, $fail) {
                    if (! $this->esPdfValido($value)) {
                        $fail('El archivo no es un PDF válido (la estructura interna no corresponde a un PDF auténtico).');
                    }
                },
            ],
        ]);

        if (! Gate::allows(Permission::crearInforme($data['tipo']))) {
            return back()->withInput()->with('error', 'No tienes permiso para asignar este tipo de informe.');
        }

        $informe->update([
            'titulo' => $data['titulo'],
            'tipo' => $data['tipo'],
            'contenido' => $data['contenido'],
        ]);

        $informe->residentes()->sync($data['residentes'] ?? []);

        // El PDF anterior NO se borra (documentación clínica). Se guarda primero el nuevo;
        // la ruta del anterior queda en la auditoría (cambio de pdf_path)
        if ($request->hasFile('pdf')) {
            $file = $request->file('pdf');
            $path = $file->storeAs('informes', $file->hashName(), 'local');
            $informe->update(['pdf_path' => $path]);
        }

        return redirect()->route('informes.index')->with('success', 'Informe actualizado correctamente.');
    }

    /** El informe y su PDF se conservan; deja de mostrarse como vigente. */
    public function anular(MotivoRequest $request, Informe $informe)
    {
        $informe->anular($request->validated('motivo'));

        return redirect()->route('informes.index')->with('success', 'Informe anulado.');
    }

    public function downloadPdf(Informe $informe)
    {
        $disco = Storage::disk('local');

        abort_unless($informe->pdf_path && $disco->exists($informe->pdf_path), 404);
        Log::registrar($informe, 'viewed', ['pantalla' => 'pdf']);

        return response()->file($disco->path($informe->pdf_path));
    }

    private function esPdfValido($archivo): bool
    {
        try {
            $parser = new Parser;
            $parser->parseFile($archivo->getPathname());

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
