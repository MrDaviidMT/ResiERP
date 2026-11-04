<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotivoRequest;
use App\Models\Log;
use App\Models\Medicacion;
use App\Models\Residente;
use Illuminate\Http\Request;

class MedicacionController extends Controller
{
    public function index($residente_id)
    {
        $residente = Residente::withTrashed()
            ->with(['medicaciones', 'medicacionesRetiradas.retiradoPor', 'habitacion'])
            ->findOrFail($residente_id);
        Log::registrar($residente, 'viewed', ['pantalla' => 'medicacion']);

        $anuladas = Medicacion::soloAnulados()->where('residente_id', $residente->id)->with('anuladoPor')->latest('anulado_at')->get()
            ->map(fn (Medicacion $m) => [
                'resumen' => "{$m->medicamento} ({$m->dosis})",
                'anulado_at' => $m->anulado_at,
                'por' => $m->anuladoPor?->name,
                'motivo' => $m->motivo_anulacion,
            ]);

        return view('medicacion.index', compact('residente', 'anuladas'));
    }

    public function store(Request $request, $residente_id)
    {
        $request->validate([
            'medicamento' => 'required|string|max:100',
            'dosis' => 'required|string|max:50',
        ]);

        $residente = Residente::withTrashed()->findOrFail($residente_id);

        Medicacion::create([
            'residente_id' => $residente->id,
            'medicamento' => $request->medicamento,
            'dosis' => $request->dosis,
            'desayuno' => $request->has('desayuno') ? true : false,
            'comida' => $request->has('comida') ? true : false,
            'cena' => $request->has('cena') ? true : false,
            'noche' => $request->has('noche') ? true : false,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->back()->with('success', 'Medicamento añadido a la pauta del residente.');
    }

    /** Decisión clínica: la pauta deja de administrarse y queda en el historial. */
    public function retirar(MotivoRequest $request, Medicacion $medicacion)
    {
        if ($medicacion->estaRetirada()) {
            return back()->with('error', 'Esta pauta ya estaba retirada.');
        }

        $medicacion->retirar($request->validated('motivo'));

        return redirect()->route('medicacion.index', $medicacion->residente_id)->with('success', 'Pauta retirada. Queda en el historial de medicación.');
    }

    /** Pauta registrada por error. */
    public function anular(MotivoRequest $request, Medicacion $medicacion)
    {
        $medicacion->anular($request->validated('motivo'));

        return redirect()->route('medicacion.index', $medicacion->residente_id)->with('success', 'Pauta anulada.');
    }
}
