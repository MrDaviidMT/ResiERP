<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use App\Models\Concerns\Anulacion;
use App\Models\Concerns\TieneAutor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * Pauta de medicación.
 * - Retirar: decisión clínica; la pauta deja de administrarse y queda en el historial con fecha y motivo.
 * - Anular: se registró por error (trait Anulable).
 */
class Medicacion extends Model
{
    use Anulable, HasFactory, TieneAutor;

    protected $table = 'medicaciones';

    protected $fillable = [
        'residente_id', 'medicamento', 'dosis', 'desayuno', 'comida', 'cena', 'noche', 'observaciones',
    ];

    protected $casts = [
        'desayuno' => 'boolean',
        'comida' => 'boolean',
        'cena' => 'boolean',
        'noche' => 'boolean',
        'fecha_fin' => 'datetime',
    ];

    public function residente()
    {
        return $this->belongsTo(Residente::class, 'residente_id');
    }

    public function retiradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retirado_por');
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('fecha_fin'));
    }

    public function scopeRetiradas(Builder $query): Builder
    {
        return $query->whereNotNull($query->qualifyColumn('fecha_fin'));
    }

    public function estaRetirada(): bool
    {
        return $this->fecha_fin !== null;
    }

    public function retirar(string $motivo): void
    {
        $motivo = trim($motivo);

        if ($this->estaRetirada()) {
            throw new LogicException('La pauta ya está retirada.');
        }
        if (mb_strlen($motivo) < Anulacion::MOTIVO_MINIMO) {
            throw new InvalidArgumentException('El motivo de retirada debe tener al menos '.Anulacion::MOTIVO_MINIMO.' caracteres.');
        }

        DB::transaction(function () use ($motivo) {
            // Acción de auditoría propia ("withdrawn") con el motivo, en lugar de un "updated" genérico
            $this->forceFill([
                'fecha_fin' => now(),
                'retirado_por' => Auth::id(),
                'motivo_retirada' => $motivo,
            ])->saveQuietly();

            Log::registrar($this, 'withdrawn', ['motivo' => $motivo]);
        });
    }

    /** Momentos de toma en texto, para mostrarlos en una línea: "Desayuno, cena". */
    public function tomas(): string
    {
        $tomas = array_filter(['Desayuno' => $this->desayuno, 'Comida' => $this->comida, 'Cena' => $this->cena, 'Noche' => $this->noche]);

        return ucfirst(mb_strtolower(implode(', ', array_keys($tomas))));
    }
}
