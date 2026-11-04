<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habitacion extends Model
{
    use HasFactory;

    protected $table = 'habitaciones';

    protected $casts = [
        'mantenimiento_manual' => 'boolean',
    ];

    protected $fillable = [
        'numero',
        'tipo',
        'estado',
        'observaciones',
    ];

    public function residentes(): HasMany
    {
        return $this->hasMany(Residente::class, 'habitacion_id');
    }

    public function capacidadMaxima(): int
    {
        return strtolower($this->tipo) === 'doble' ? 2 : 1;
    }

    public function tienePlazasLibres(): bool
    {
        if (strtolower($this->estado) === 'mantenimiento') {
            return false;
        }

        return $this->residentes_count < $this->capacidadMaxima();
    }

    /**
     * Si la habitación está en mantenimiento por incidencias (no a mano) y ya no queda ninguna
     * incidencia bloqueante activa (las anuladas no cuentan), vuelve a estar disponible.
     */
    public function liberarSiNoHayIncidenciasBloqueantes(): void
    {
        if (strtolower($this->estado) !== 'mantenimiento' || $this->mantenimiento_manual) {
            return;
        }

        if (Incidencia::where('habitacion_id', $this->id)->bloqueantesActivas()->exists()) {
            return;
        }

        $this->update(['estado' => $this->residentes()->exists() ? 'ocupada' : 'libre']);
    }

    public function sincronizarEstado(): void
    {
        if (strtolower($this->estado) === 'mantenimiento') {
            return;
        }

        $count = $this->residentes()->count();
        $this->update(['estado' => $count > 0 ? 'ocupada' : 'libre']);
    }
}
