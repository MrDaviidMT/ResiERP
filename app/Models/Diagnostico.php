<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use App\Models\Concerns\TieneAutor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnostico extends Model
{
    use Anulable, HasFactory, TieneAutor;

    protected $fillable = [
        'residente_id',
        'nombre',
        'descripcion',
        'tipo',
        'fecha_diagnostico',
        'activo',
        'observaciones',
        'tratamiento',
        'medico',
        'especialidad',
    ];

    protected $casts = [
        'fecha_diagnostico' => 'date',
        'activo' => 'boolean',
    ];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
