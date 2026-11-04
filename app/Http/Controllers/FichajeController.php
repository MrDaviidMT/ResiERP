<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Requests\MotivoRequest;
use App\Models\Concerns\Anulacion;
use App\Models\Fichaje;
use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FichajeController extends Controller
{
    public function index(Request $request)
    {
        $fichajes = $this->consulta($request)->with('user', 'editor')->latest('entrada')->paginate(30);

        $usuarios = auth()->user()->can(Permission::FichajesVerTodos)
            ? User::withoutRole(Role::Superadmin)->orderBy('name')->get()
            : collect();

        // Anulados, con la misma visibilidad que los vigentes (los propios, o todos con permiso)
        $anulados = Fichaje::soloAnulados()->with(['user', 'anuladoPor'])
            ->when(! auth()->user()->can(Permission::FichajesVerTodos), fn ($q) => $q->where('user_id', auth()->id()))
            ->latest('anulado_at')->get()
            ->map(fn (Fichaje $f) => [
                'resumen' => $f->user?->name.' — entrada '.$f->entrada?->format('d/m/Y H:i'),
                'anulado_at' => $f->anulado_at, 'por' => $f->anuladoPor?->name, 'motivo' => $f->motivo_anulacion,
            ]);

        return view('fichajes.index', compact('fichajes', 'usuarios', 'anulados'));
    }

    public function create()
    {
        $usuarios = User::withoutRole(Role::Superadmin)->orderBy('name')->get();

        return view('fichajes.create', compact('usuarios'));
    }

    public function show(Fichaje $fichaje)
    {
        // El trabajador ve todas las correcciones que se han hecho sobre su fichaje
        $fichaje->load('user', 'editor', 'correcciones.corregidoPor');

        return view('fichajes.show', compact('fichaje'));
    }

    public function store(Request $request)
    {
        $isAdmin = auth()->user()->can(Permission::FichajesCrearOtros);

        if ($isAdmin && ($request->filled('entrada_fecha') || $request->filled('entrada_hora'))) {
            $request->merge([
                'entrada' => $request->entrada_fecha && $request->entrada_hora
                    ? $request->entrada_fecha.' '.$request->entrada_hora.':00'
                    : null,
                'salida' => $request->salida_fecha && $request->salida_hora
                    ? $request->salida_fecha.' '.$request->salida_hora.':00'
                    : null,
            ]);

            $data = $request->validate([
                // Nadie se crea fichajes a sí mismo a mano (ET art. 34.9): eso sería alterar su propio registro
                'user_id' => ['required', 'exists:users,id', Rule::notIn([auth()->id()])],
                'entrada' => 'required|date',
                'salida' => 'nullable|date|after:entrada',
                'notas' => 'nullable|string|max:500',
                'motivo' => 'required|string|min:'.Anulacion::MOTIVO_MINIMO.'|max:1000',
            ], ['user_id.not_in' => 'No puedes registrar a mano tus propios fichajes.']);

            Fichaje::registrarManual((int) $data['user_id'], $data['entrada'], $data['salida'] ?? null, $data['notas'] ?? null, $data['motivo'], $request->ip());

            return redirect()->route('fichajes.index')
                ->with('success', 'Fichaje creado correctamente. Queda registrado como corrección con su motivo.');
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notas' => 'nullable|string|max:500',
        ]);

        if (! $isAdmin && (int) $data['user_id'] !== auth()->id()) {
            return redirect()->route('fichajes.index')->with('error', 'No tienes permiso para fichar a otro usuario.');
        }

        return DB::transaction(function () use ($data, $request) {
            // Bloquea al usuario para que dos peticiones simultáneas no abran dos fichajes
            User::lockForUpdate()->findOrFail($data['user_id']);

            $abierto = Fichaje::where('user_id', $data['user_id'])->whereNull('salida')->exists();
            if ($abierto) {
                return redirect()->route('dashboard')
                    ->with('error', 'Ya hay un fichaje de entrada abierto. Registra la salida antes de fichar de nuevo.');
            }

            Fichaje::create([
                'user_id' => $data['user_id'],
                'entrada' => now(),
                'ip_address' => $request->ip(),
                'notas' => $data['notas'] ?? null,
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Fichaje de entrada registrado correctamente.');
        });
    }

    public function marcarSalida(Fichaje $fichaje)
    {
        if ($fichaje->salida) {
            return back()->with('info', 'Este fichaje ya tiene registrada la salida.');
        }

        $fichaje->update(['salida' => now()]);

        return redirect()->route('dashboard')
            ->with('success', 'Salida registrada correctamente.');
    }

    public function edit(Fichaje $fichaje)
    {
        $fichaje->load('user', 'editor');

        return view('fichajes.edit', compact('fichaje'));
    }

    public function update(Request $request, Fichaje $fichaje)
    {
        $request->merge([
            'entrada' => $request->entrada_fecha && $request->entrada_hora
                ? $request->entrada_fecha.' '.$request->entrada_hora.':00'
                : null,
            'salida' => $request->salida_fecha && $request->salida_hora
                ? $request->salida_fecha.' '.$request->salida_hora.':00'
                : null,
        ]);

        $data = $request->validate([
            'entrada' => 'required|date',
            'salida' => 'nullable|date|after:entrada',
            'notas' => 'nullable|string|max:500',
            'motivo' => 'required|string|min:'.Anulacion::MOTIVO_MINIMO.'|max:1000',
        ]);

        // ET art. 34.9: queda el valor anterior, el nuevo, el motivo y quién lo corrigió
        $fichaje->corregir($data['entrada'], $data['salida'] ?? null, $data['notas'] ?? null, $data['motivo']);

        return redirect()->route('fichajes.show', $fichaje)
            ->with('success', 'Fichaje actualizado correctamente.');
    }

    /** El registro de jornada no se borra; un fichaje erróneo se anula con motivo. */
    public function anular(MotivoRequest $request, Fichaje $fichaje)
    {
        $fichaje->anular($request->validated('motivo'));

        return redirect()->route('fichajes.index')->with('success', 'Fichaje anulado.');
    }

    /**
     * Exporta el registro de jornada en CSV (ET art. 34.9: debe poder entregarse al trabajador,
     * a sus representantes y a la Inspección de Trabajo). Incluye los anulados y las correcciones,
     * porque el registro tiene que ser trazable.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $fichajes = $this->consulta($request)->conAnulados()
            ->with(['user', 'anuladoPor', 'correcciones.corregidoPor'])
            ->orderBy('entrada')
            ->get();

        Log::registrar($request->user(), 'exported', [
            'tipo' => 'registro_jornada',
            'fichajes' => $fichajes->count(),
            'filtros' => $request->only(['user_id', 'desde', 'hasta']),
        ]);

        $nombre = 'registro-jornada-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($fichajes) {
            $salida = fopen('php://output', 'w');
            // BOM: para que Excel abra el fichero con los acentos correctos
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Empleado', 'Entrada', 'Salida', 'Horas', 'Estado', 'Motivo de anulación', 'Correcciones', 'Detalle de correcciones', 'IP', 'Notas'], ';');

            foreach ($fichajes as $fichaje) {
                fputcsv($salida, [
                    $fichaje->user?->name ?? '—',
                    $fichaje->entrada?->format('d/m/Y H:i'),
                    $fichaje->salida?->format('d/m/Y H:i') ?? '',
                    $fichaje->salida ? number_format($fichaje->entrada->diffInMinutes($fichaje->salida) / 60, 2, ',', '') : '',
                    $fichaje->estaAnulado() ? 'Anulado' : 'Vigente',
                    $fichaje->motivo_anulacion ?? '',
                    $fichaje->correcciones->count(),
                    $fichaje->correcciones->map(fn ($c) => $c->created_at->format('d/m/Y H:i').' ('.($c->corregidoPor?->name ?? '—').'): '.$c->motivo)->join(' | '),
                    $fichaje->ip_address ?? '',
                    $fichaje->notas ?? '',
                ], ';');
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Filtros comunes al listado y a la exportación: cada empleado solo ve sus fichajes salvo permiso. */
    private function consulta(Request $request): Builder
    {
        $query = Fichaje::query();

        if (! $request->user()->can(Permission::FichajesVerTodos)) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        return $query
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('entrada', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('entrada', '<=', $request->input('hasta')));
    }
}
