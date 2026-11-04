<?php

namespace App\Models\Concerns;

use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * Registros que la ley obliga a conservar: no se borran, se anulan con motivo.
 *
 * - Lo anulado queda fuera de todas las consultas por defecto (falla en lo seguro: una pauta
 *   anulada nunca aparece como vigente). Para verlo: conAnulados() o soloAnulados().
 * - Una vez anulado, el registro no se puede editar; la anulación no se revierte.
 * - El borrado físico está prohibido a nivel de modelo.
 *
 * Requiere las columnas anulado_at, anulado_por y motivo_anulacion.
 */
trait Anulable
{
    public static function bootAnulable(): void
    {
        static::addGlobalScope('sin_anulados', function (Builder $query) {
            $query->whereNull($query->qualifyColumn('anulado_at'));
        });

        static::updating(function (self $registro) {
            if ($registro->getOriginal('anulado_at') !== null) {
                throw new LogicException('Un registro anulado no se puede modificar.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Este registro no se borra: se anula con motivo.');
        });
    }

    public function initializeAnulable(): void
    {
        $this->mergeCasts(['anulado_at' => 'datetime']);
    }

    public function scopeConAnulados(Builder $query): Builder
    {
        return $query->withoutGlobalScope('sin_anulados');
    }

    public function scopeSoloAnulados(Builder $query): Builder
    {
        return $query->withoutGlobalScope('sin_anulados')->whereNotNull($query->qualifyColumn('anulado_at'));
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function estaAnulado(): bool
    {
        return $this->anulado_at !== null;
    }

    public function anular(string $motivo): void
    {
        $motivo = trim($motivo);

        if ($this->estaAnulado()) {
            throw new LogicException('El registro ya está anulado.');
        }
        if (mb_strlen($motivo) < Anulacion::MOTIVO_MINIMO) {
            throw new InvalidArgumentException('El motivo de anulación debe tener al menos '.Anulacion::MOTIVO_MINIMO.' caracteres.');
        }

        DB::transaction(function () use ($motivo) {
            // saveQuietly: la auditoría registra una acción propia ("annulled") con el motivo,
            // en lugar de un "updated" genérico
            $this->forceFill([
                'anulado_at' => now(),
                'anulado_por' => Auth::id(),
                'motivo_anulacion' => $motivo,
            ])->saveQuietly();

            Log::registrar($this, 'annulled', ['motivo' => $motivo]);
        });
    }
}
