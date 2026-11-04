<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use App\Models\Concerns\TieneAutor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstanteVital extends Model
{
    use Anulable, HasFactory, TieneAutor;

    protected $table = 'constantes_vitales';

    protected $fillable = [
        'residente_id',
        'tension_sistolica',
        'tension_diastolica',
        'frecuencia_cardiaca',
        'temperatura',
        'peso',
        'saturacion_oxigeno',
        'glucosa',
        'fecha_registro',
        'observaciones',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
    ];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
