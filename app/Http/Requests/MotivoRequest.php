<?php

namespace App\Http\Requests;

use App\Models\Concerns\Anulacion;
use Illuminate\Foundation\Http\FormRequest;

// Acciones que exigen motivo (anular, retirar una pauta...). La autorización la hace la ruta.
class MotivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // El trait exige 10 caracteres sin espacios alrededor; el FormRequest recorta antes de validar (TrimStrings)
        return [
            'motivo' => ['required', 'string', 'min:'.Anulacion::MOTIVO_MINIMO, 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['motivo' => 'motivo'];
    }
}
