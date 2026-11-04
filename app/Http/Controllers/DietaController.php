<?php

namespace App\Http\Controllers;

use App\Models\Residente;
use Illuminate\Http\Request;

class DietaController extends Controller
{
    public function index(Residente $residente)
    {
        $residente->load('dieta');

        return view('residentes.dieta.index', compact('residente'));
    }

    public function store(Request $request, Residente $residente)
    {
        $data = $request->validate([
            'tipo' => 'required|in:normal,diabetica,hipocalorica,sin_sal,triturada,blanda,renal,otras',
            'alergias_alimentarias' => 'nullable|string',
            'preferencias' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        // D7: update() sobre la relación es una consulta y no dispara el Observer de auditoría
        if ($residente->dieta) {
            $residente->dieta->update($data);
            $msg = 'Dieta actualizada correctamente.';
        } else {
            $residente->dieta()->create($data);
            $msg = 'Dieta asignada correctamente.';
        }

        return redirect()->route('dieta.index', $residente)->with('success', $msg);
    }
}
