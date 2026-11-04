<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotivoRequest;
use App\Models\Residente;
use App\Models\Visita;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisitaController extends Controller
{
    public function index(Residente $residente)
    {
        $residente->load(['visitas' => fn ($q) => $q->latest('entrada'), 'familiares', 'familiaresAutorizados']);

        $anuladas = Visita::soloAnulados()->where('residente_id', $residente->id)->with('anuladoPor')->latest('anulado_at')->get()
            ->map(fn (Visita $v) => ['resumen' => $v->nombre_visitante.' — '.$v->entrada?->format('d/m/Y H:i'), 'anulado_at' => $v->anulado_at, 'por' => $v->anuladoPor?->name, 'motivo' => $v->motivo_anulacion]);

        return view('residentes.visitas.index', compact('residente', 'anuladas'));
    }

    public function store(Request $request, Residente $residente)
    {
        $data = $request->validate($this->reglas($residente));

        $residente->visitas()->create($data);

        return redirect()->route('visitas.index', $residente)->with('success', 'Visita registrada.');
    }

    public function registrarSalida(Visita $visita)
    {
        if ($visita->salida || $visita->entrada->isFuture()) {
            return redirect()->route('visitas.index', $visita->residente_id)
                ->with('error', 'No se puede registrar la salida de esta visita.');
        }
        $visita->update(['salida' => now()]);

        return redirect()->route('visitas.index', $visita->residente_id)
            ->with('success', 'Salida registrada.');
    }

    /** El registro de accesos se conserva; la visita se anula con motivo. */
    public function anular(MotivoRequest $request, Visita $visita)
    {
        $visita->anular($request->validated('motivo'));

        return redirect()->route('visitas.index', $visita->residente_id)->with('success', 'Registro de visita anulado.');
    }

    public function update(Request $request, Visita $visita)
    {
        $data = $request->validate($this->reglas($visita->residente));

        $visita->update($data);

        return redirect()->route('visitas.index', $visita->residente_id)
            ->with('success', 'Visita actualizada.');
    }

    /**
     * Una visita solo se atribuye a un familiar vinculado a ESE residente y autorizado a visitarle.
     * Sin familiar es una visita de alguien no registrado, que sí se permite.
     */
    private function reglas(Residente $residente): array
    {
        return [
            'familiar_id' => [
                'nullable',
                Rule::in($residente->familiaresAutorizados()->pluck('familiares.id')->all()),
            ],
            'nombre_visitante' => 'required|string|max:255',
            'documento' => 'required|string|max:20',
            'entrada' => 'required|date',
            'observaciones' => 'nullable|string',
        ];
    }
}
