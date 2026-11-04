<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Corrección de un fichaje (Estatuto de los Trabajadores art. 34.9).
 * Historial de solo inserción: una corrección no se modifica ni se borra nunca.
 */
class FichajeCorreccion extends Model
{
    protected $table = 'fichaje_correcciones';

    public const UPDATED_AT = null;

    protected $fillable = ['fichaje_id', 'entrada_anterior', 'salida_anterior', 'entrada_nueva', 'salida_nueva', 'motivo', 'corregido_por'];

    protected $casts = [
        'entrada_anterior' => 'datetime',
        'salida_anterior' => 'datetime',
        'entrada_nueva' => 'datetime',
        'salida_nueva' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Una corrección de fichaje no se puede modificar.'));
        static::deleting(fn () => throw new LogicException('Una corrección de fichaje no se puede borrar.'));
    }

    public function fichaje(): BelongsTo
    {
        return $this->belongsTo(Fichaje::class);
    }

    public function corregidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corregido_por');
    }
}
