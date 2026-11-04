<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use App\Models\Concerns\TieneAutor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitaMedica extends Model
{
    use Anulable, HasFactory, TieneAutor;

    protected $table = 'visitas_medicas';

    protected $fillable = [
        'residente_id',
        'medico',
        'especialidad',
        'motivo',
        'fecha_visita',
    ];

    protected $casts = [
        'fecha_visita' => 'datetime',
    ];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
