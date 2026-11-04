<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visita extends Model
{
    use Anulable, HasFactory;

    protected $fillable = [
        'residente_id',
        'familiar_id',
        'nombre_visitante',
        'documento',
        'entrada',
        'salida',
        'observaciones',
    ];

    protected $casts = [
        'entrada' => 'datetime',
        'salida' => 'datetime',
    ];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }

    public function familiar(): BelongsTo
    {
        return $this->belongsTo(Familiar::class);
    }

    public function getDuracionAttribute(): ?string
    {
        if (! $this->salida) {
            return null;
        }
        $diff = $this->entrada->diff($this->salida);
        $h = $diff->h + ($diff->days * 24);

        return "{$h}h {$diff->i}m";
    }

    public function getEnCursoAttribute(): bool
    {
        return is_null($this->salida) && $this->entrada->isPast();
    }
}
