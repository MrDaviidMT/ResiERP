<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use App\Models\Concerns\TieneAutor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alergia extends Model
{
    use Anulable, HasFactory, TieneAutor;

    protected $fillable = [
        'residente_id',
        'alergeno',
        'reaccion',
        'severidad',
        'fecha_deteccion',
        'fecha_resolucion',
        'activa',
        'observaciones',
    ];

    protected $casts = [
        'fecha_deteccion' => 'date',
        'fecha_resolucion' => 'date',
        'activa' => 'boolean',
    ];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
