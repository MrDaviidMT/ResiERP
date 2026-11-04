<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends Model
{
    use Anulable, HasFactory;

    protected $table = 'incidencias';

    protected $fillable = [
        'habitacion_id',
        'residente_id',
        'titulo',
        'descripcion',
        'categoria',
        'estado',
        'prioridad',
        'fecha_resolucion',
    ];

    protected $casts = [
        'fecha_resolucion' => 'datetime',
    ];

    public const ESTADOS = ['pendiente', 'en_proceso', 'resuelta'];

    // Estado actual => estados a los que se puede pasar
    public const TRANSICIONES = [
        'pendiente' => ['en_proceso'],
        'en_proceso' => ['resuelta', 'pendiente'],
        'resuelta' => ['en_proceso'],
    ];

    /** Una incidencia de infraestructura no leve pone su habitación en mantenimiento mientras está activa. */
    public static function bloquea(string $categoria, string $prioridad): bool
    {
        return $categoria === 'infraestructura' && $prioridad !== 'leve';
    }

    public function scopeBloqueantesActivas(Builder $query): Builder
    {
        return $query->where('categoria', 'infraestructura')
            ->where('prioridad', '!=', 'leve')
            ->whereIn('estado', ['pendiente', 'en_proceso']);
    }

    public function puedeCambiarA(string $estado): bool
    {
        return in_array($estado, self::TRANSICIONES[$this->estado] ?? [], true);
    }

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_id');
    }

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class, 'residente_id')->withTrashed();
    }
}
