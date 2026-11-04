<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotivoRequest;
use App\Models\Alergia;
use App\Models\ConstanteVital;
use App\Models\CuidadoDiario;
use App\Models\Diagnostico;
use App\Models\Informe;
use App\Models\Log;
use App\Models\Residente;
use App\Models\VisitaMedica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class HistorialMedicoController extends Controller
{
    public function index(Residente $residente)
    {
        $residente->load([
            'diagnosticos',
            'alergias',
            'constantes' => fn ($q) => $q->latest('fecha_registro'),
            'cuidadosDiarios' => fn ($q) => $q->latest(),
            'cuidadosDiarios.autor',
            'informes' => fn ($q) => $q->visiblesPara(auth()->user())->latest(),
            'informes.user',
            'visitasMedicas' => fn ($q) => $q->latest('fecha_visita'),
            'habitacion',
        ]);

        $ultimasConstantes = $residente->constantes->take(5);

        Log::registrar($residente, 'viewed', ['pantalla' => 'historial']);

        $anulados = [
            'diagnosticos' => $this->anulados(Diagnostico::class, $residente, fn ($d) => $d->nombre.' ('.$d->fecha_diagnostico?->format('d/m/Y').')'),
            'alergias' => $this->anulados(Alergia::class, $residente, fn ($a) => $a->alergeno.' — '.$a->severidad),
            'constantes' => $this->anulados(ConstanteVital::class, $residente, fn ($c) => 'Constantes del '.$c->fecha_registro?->format('d/m/Y H:i')),
            'cuidados' => $this->anulados(CuidadoDiario::class, $residente, fn ($c) => 'Cuidado del '.$c->fecha?->format('d/m/Y')),
            'visitas' => $this->anulados(VisitaMedica::class, $residente, fn ($v) => $v->medico.' — '.$v->motivo.' ('.$v->fecha_visita?->format('d/m/Y').')'),
            'informes' => $residente->informes()->soloAnulados()->visiblesPara(auth()->user())->with('anuladoPor')->latest('anulado_at')->get()
                ->map(fn (Informe $i) => [
                    'resumen' => $i->titulo,
                    'anulado_at' => $i->anulado_at,
                    'por' => $i->anuladoPor?->name,
                    'motivo' => $i->motivo_anulacion,
                ]),
        ];

        return view('residentes.historial.index', compact('residente', 'ultimasConstantes', 'anulados'));
    }

    public function storeDiagnostico(Request $request, Residente $residente)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:cronico,agudo,quirurgico,infeccioso,degenerativo,congenito,autoinmune,metabolico,traumatico,funcional,otro',
            'fecha_diagnostico' => 'required|date|before_or_equal:today',
            'observaciones' => 'nullable|string',
            'tratamiento' => 'nullable|string',
            'medico' => 'nullable|string|max:255',
            'especialidad' => 'nullable|string|max:255',
        ]);

        $residente->diagnosticos()->create($data);

        return redirect()->route('residentes.historial', $residente)
            ->with('success', 'Diagnóstico registrado correctamente.');
    }

    public function toggleDiagnostico(Diagnostico $diagnostico)
    {
        $diagnostico->update(['activo' => ! $diagnostico->activo]);

        return redirect()->route('residentes.historial', $diagnostico->residente_id)
            ->with('success', 'Estado del diagnóstico actualizado.');
    }

    public function anularDiagnostico(MotivoRequest $request, Diagnostico $diagnostico)
    {
        return $this->anularRegistro($request, $diagnostico, 'Diagnóstico anulado.');
    }

    public function storeConstante(Request $request, Residente $residente)
    {
        $validator = Validator::make($request->all(), [
            'tension_sistolica' => 'nullable|integer|min:30|max:300',
            'tension_diastolica' => 'nullable|integer|min:20|max:200',
            'frecuencia_cardiaca' => 'nullable|integer|min:20|max:300',
            'temperatura' => 'nullable|numeric|min:30|max:45',
            'peso' => 'nullable|numeric|min:1|max:300',
            'saturacion_oxigeno' => 'nullable|integer|min:30|max:100',
            'glucosa' => 'nullable|numeric|min:10|max:600',
            'fecha_registro' => 'required|date|before_or_equal:now',
            'observaciones' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            $camposMedicos = ['tension_sistolica', 'tension_diastolica', 'frecuencia_cardiaca', 'temperatura', 'peso', 'saturacion_oxigeno', 'glucosa'];
            $tieneDato = collect($camposMedicos)->contains(fn ($c) => $request->filled($c));
            if (! $tieneDato) {
                $validator->errors()->add('tension_sistolica', 'Debe registrar al menos un valor médico.');
            }

            // Una tensión sistólica por debajo de la diastólica es imposible
            if ($request->filled('tension_sistolica') && $request->filled('tension_diastolica')
                && (int) $request->input('tension_sistolica') <= (int) $request->input('tension_diastolica')) {
                $validator->errors()->add('tension_sistolica', 'La tensión sistólica debe ser mayor que la diastólica.');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $residente->constantes()->create($data);

        return redirect()->route('residentes.historial', $residente)
            ->with('success', 'Constantes vitales registradas.');
    }

    public function storeCuidadoDiario(Request $request, Residente $residente)
    {
        $data = $request->validate([
            'fecha' => 'required|date',
            'comio_bien' => 'nullable|boolean',
            'se_baño' => 'nullable|boolean',
            'observaciones' => 'nullable|string',
        ]);

        $residente->cuidadosDiarios()->create([
            'fecha' => $data['fecha'],
            'comio_bien' => $request->boolean('comio_bien'),
            'se_baño' => $request->boolean('se_baño'),
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        return redirect()->route('residentes.historial', $residente)
            ->with('success', 'Cuidado diario registrado.');
    }

    public function updateCuidadoDiario(Request $request, CuidadoDiario $cuidadoDiario)
    {
        $data = $request->validate([
            'fecha' => 'required|date',
            'comio_bien' => 'nullable|boolean',
            'se_baño' => 'nullable|boolean',
            'observaciones' => 'nullable|string',
        ]);

        $cuidadoDiario->update([
            'fecha' => $data['fecha'],
            'comio_bien' => $request->boolean('comio_bien'),
            'se_baño' => $request->boolean('se_baño'),
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        return redirect()->route('residentes.historial', $cuidadoDiario->residente_id)
            ->with('success', 'Cuidado diario actualizado.');
    }

    public function anularCuidadoDiario(MotivoRequest $request, CuidadoDiario $cuidadoDiario)
    {
        return $this->anularRegistro($request, $cuidadoDiario, 'Cuidado diario anulado.');
    }

    public function storeVisita(Request $request, Residente $residente)
    {
        $data = $request->validate([
            'medico' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'motivo' => 'required|string|max:255',
            'fecha_visita' => 'required|date',
        ]);

        $residente->visitasMedicas()->create($data);

        return redirect()->route('residentes.historial', $residente)
            ->with('success', 'Visita médica registrada.');
    }

    public function anularVisita(MotivoRequest $request, VisitaMedica $visita)
    {
        return $this->anularRegistro($request, $visita, 'Visita médica anulada.');
    }

    public function updateDiagnostico(Request $request, Diagnostico $diagnostico)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:cronico,agudo,quirurgico,infeccioso,degenerativo,congenito,autoinmune,metabolico,traumatico,funcional,otro',
            'fecha_diagnostico' => 'required|date|before_or_equal:today',
            'observaciones' => 'nullable|string',
            'tratamiento' => 'nullable|string',
            'medico' => 'nullable|string|max:255',
            'especialidad' => 'nullable|string|max:255',
        ]);

        $diagnostico->update($data);

        return redirect()->route('residentes.historial', $diagnostico->residente_id)
            ->with('success', 'Diagnóstico actualizado correctamente.');
    }

    public function updateConstante(Request $request, ConstanteVital $constante)
    {
        $validator = Validator::make($request->all(), [
            'tension_sistolica' => 'nullable|integer|min:30|max:300',
            'tension_diastolica' => 'nullable|integer|min:20|max:200',
            'frecuencia_cardiaca' => 'nullable|integer|min:20|max:300',
            'temperatura' => 'nullable|numeric|min:30|max:45',
            'peso' => 'nullable|numeric|min:1|max:300',
            'saturacion_oxigeno' => 'nullable|integer|min:30|max:100',
            'glucosa' => 'nullable|numeric|min:10|max:600',
            'fecha_registro' => 'required|date|before_or_equal:now',
            'observaciones' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            $camposMedicos = ['tension_sistolica', 'tension_diastolica', 'frecuencia_cardiaca', 'temperatura', 'peso', 'saturacion_oxigeno', 'glucosa'];
            $tieneDato = collect($camposMedicos)->contains(fn ($c) => $request->filled($c));
            if (! $tieneDato) {
                $validator->errors()->add('tension_sistolica', 'Debe registrar al menos un valor médico.');
            }

            // Una tensión sistólica por debajo de la diastólica es imposible
            if ($request->filled('tension_sistolica') && $request->filled('tension_diastolica')
                && (int) $request->input('tension_sistolica') <= (int) $request->input('tension_diastolica')) {
                $validator->errors()->add('tension_sistolica', 'La tensión sistólica debe ser mayor que la diastólica.');
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $constante->update($data);

        return redirect()->route('residentes.historial', $constante->residente_id)
            ->with('success', 'Constantes vitales actualizadas.');
    }

    public function anularConstante(MotivoRequest $request, ConstanteVital $constante)
    {
        return $this->anularRegistro($request, $constante, 'Registro de constantes anulado.');
    }

    public function updateVisitaMedica(Request $request, VisitaMedica $visitaMedica)
    {
        $data = $request->validate([
            'medico' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'motivo' => 'required|string|max:255',
            'fecha_visita' => 'required|date',
        ]);

        $visitaMedica->update($data);

        return redirect()->route('residentes.historial', $visitaMedica->residente_id)
            ->with('success', 'Visita médica actualizada.');
    }

    public function storeAlergia(Request $request, Residente $residente)
    {
        $data = $request->validate([
            'alergeno' => 'required|string|max:255',
            'reaccion' => 'nullable|string',
            'severidad' => 'required|in:leve,moderada,grave',
            'fecha_deteccion' => 'nullable|date|before_or_equal:today',
            'fecha_resolucion' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        $residente->alergias()->create($data);

        return redirect()->route('residentes.historial', $residente)
            ->with('success', 'Alergia registrada correctamente.');
    }

    public function updateAlergia(Request $request, Alergia $alergia)
    {
        $data = $request->validate([
            'alergeno' => 'required|string|max:255',
            'reaccion' => 'nullable|string',
            'severidad' => 'required|in:leve,moderada,grave',
            'fecha_deteccion' => 'nullable|date|before_or_equal:today',
            'fecha_resolucion' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        $alergia->update($data);

        return redirect()->route('residentes.historial', $alergia->residente_id)
            ->with('success', 'Alergia actualizada correctamente.');
    }

    public function anularAlergia(MotivoRequest $request, Alergia $alergia)
    {
        return $this->anularRegistro($request, $alergia, 'Alergia anulada.');
    }

    public function toggleAlergia(Alergia $alergia)
    {
        $alergia->update(['activa' => ! $alergia->activa]);

        return redirect()->route('residentes.historial', $alergia->residente_id)
            ->with('success', 'Estado de la alergia actualizado.');
    }

    /** Los registros clínicos no se borran, se anulan con motivo. */
    private function anularRegistro(MotivoRequest $request, Model $registro, string $mensaje): RedirectResponse
    {
        $registro->anular($request->validated('motivo'));

        return redirect()->route('residentes.historial', $registro->residente_id)->with('success', $mensaje);
    }

    /**
     * Registros anulados de un residente, resumidos para la vista (con quién, cuándo y por qué).
     *
     * @param  class-string<Model>  $modelo
     */
    private function anulados(string $modelo, Residente $residente, \Closure $resumen): Collection
    {
        return $modelo::soloAnulados()
            ->where('residente_id', $residente->id)
            ->with('anuladoPor')
            ->latest('anulado_at')
            ->get()
            ->map(fn (Model $registro) => [
                'resumen' => $resumen($registro),
                'anulado_at' => $registro->anulado_at,
                'por' => $registro->anuladoPor?->name,
                'motivo' => $registro->motivo_anulacion,
            ]);
    }

    /**
     * Copia de la historia clínica para el derecho de acceso (Ley 41/2002 art. 18, RGPD art. 15).
     * Incluye los registros anulados, marcados como tales: la historia debe ser trazable.
     */
    public function exportar(Residente $residente)
    {
        Log::registrar($residente, 'exported', ['tipo' => 'historia_clinica']);

        $secciones = [
            'Diagnósticos' => $this->seccion($residente->diagnosticos()->conAnulados()->with('autor', 'anuladoPor')->orderBy('fecha_diagnostico')->get(),
                fn ($d) => $d->fecha_diagnostico?->format('d/m/Y'), fn ($d) => $d->nombre.' ('.$d->tipo.')'.($d->tratamiento ? ' — Tratamiento: '.$d->tratamiento : '')),
            'Alergias' => $this->seccion($residente->alergias()->conAnulados()->with('autor', 'anuladoPor')->get(),
                fn ($a) => $a->fecha_deteccion?->format('d/m/Y'), fn ($a) => $a->alergeno.' — '.$a->severidad.($a->reaccion ? ' — '.$a->reaccion : '')),
            'Constantes vitales' => $this->seccion($residente->constantes()->conAnulados()->with('autor', 'anuladoPor')->orderBy('fecha_registro')->get(),
                fn ($c) => $c->fecha_registro?->format('d/m/Y H:i'), fn ($c) => collect([
                    $c->tension_sistolica && $c->tension_diastolica ? 'TA '.$c->tension_sistolica.'/'.$c->tension_diastolica : null,
                    $c->frecuencia_cardiaca ? 'FC '.$c->frecuencia_cardiaca : null,
                    $c->temperatura ? 'Tª '.$c->temperatura.' °C' : null,
                    $c->saturacion_oxigeno ? 'SatO₂ '.$c->saturacion_oxigeno.' %' : null,
                    $c->glucosa ? 'Glucosa '.$c->glucosa : null,
                    $c->peso ? 'Peso '.$c->peso.' kg' : null,
                ])->filter()->join(' · ')),
            'Medicación' => $this->seccion($residente->medicaciones()->conAnulados()->with('autor', 'anuladoPor')->get(),
                fn ($m) => $m->created_at?->format('d/m/Y'), fn ($m) => $m->medicamento.' '.$m->dosis.($m->estaRetirada() ? ' (retirada el '.$m->fecha_fin->format('d/m/Y').': '.$m->motivo_retirada.')' : '')),
            'Visitas médicas' => $this->seccion($residente->visitasMedicas()->conAnulados()->with('autor', 'anuladoPor')->orderBy('fecha_visita')->get(),
                fn ($v) => $v->fecha_visita?->format('d/m/Y'), fn ($v) => $v->medico.($v->especialidad ? ' ('.$v->especialidad.')' : '').' — '.$v->motivo),
            'Cuidados diarios' => $this->seccion($residente->cuidadosDiarios()->conAnulados()->with('autor', 'anuladoPor')->orderBy('fecha')->get(),
                fn ($c) => $c->fecha?->format('d/m/Y'), fn ($c) => 'Comió bien: '.($c->comio_bien ? 'sí' : 'no').' · Aseo: '.($c->se_baño ? 'sí' : 'no').($c->observaciones ? ' — '.$c->observaciones : '')),
            'Informes' => $this->seccion($residente->informes()->conAnulados()->with('autor', 'anuladoPor')->get(),
                fn ($i) => $i->created_at?->format('d/m/Y'), fn ($i) => $i->titulo.' ('.(Informe::TIPOS[$i->tipo] ?? $i->tipo).')'),
        ];

        return view('residentes.historial.exportar', compact('residente', 'secciones'));
    }

    /** Normaliza una sección del historial para la copia: fecha, detalle, profesional y si está anulada. */
    private function seccion(Collection $registros, \Closure $fecha, \Closure $detalle): Collection
    {
        return $registros->map(fn (Model $r) => [
            'fecha' => $fecha($r) ?? '—',
            'detalle' => $detalle($r),
            'autor' => $r->autor?->name ?? '—',
            'anulado' => $r->estaAnulado(),
            'motivo' => $r->motivo_anulacion,
        ]);
    }
}
