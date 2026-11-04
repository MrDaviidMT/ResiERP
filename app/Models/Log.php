<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'user_id',
        'loggable_type',
        'loggable_id',
        'action',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * Único punto de escritura de la auditoría: añade quién, desde qué IP y con qué navegador.
     * Solo se registran acciones de usuarios autenticados (no seeders ni consola).
     */
    public static function registrar(Model $model, string $action, ?array $changes = null): void
    {
        if (! Auth::check()) {
            return;
        }

        self::create([
            'user_id' => Auth::id(),
            'loggable_type' => $model::class,
            'loggable_id' => $model->getKey(),
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255) ?: null,
        ]);
    }

    /**
     * La auditoría solo se amplía: un registro escrito no se modifica nunca.
     * Se puede borrar porque la política de conservación elimina la auditoría vencida.
     */
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Un registro de auditoría no se puede modificar.'));
    }

    /**
     * Acción del sistema, sin usuario ni registro asociado: tareas programadas (conservación).
     * No exige sesión abierta porque la ejecuta cron.
     */
    public static function registrarSistema(string $action, ?array $changes = null): void
    {
        self::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'changes' => $changes,
            'ip_address' => null,
            'user_agent' => 'sistema',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}
