<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Fichaje extends Model
{
    use Anulable, HasFactory;

    protected $table = 'fichajes';

    protected $fillable = [
        'user_id',
        'entrada',
        'salida',
        'ip_address',
        'notas',
        'edited_by',
        'edited_at',
    ];

    protected $casts = [
        'entrada' => 'datetime',
        'salida' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function correcciones(): HasMany
    {
        return $this->hasMany(FichajeCorreccion::class)->latest('created_at');
    }

    /**
     * Corrige las horas dejando constancia (valor anterior, nuevo, motivo y quién): ET art. 34.9.
     * La transacción garantiza que no hay cambio de horas sin su corrección registrada.
     */
    public function corregir(string $entrada, ?string $salida, ?string $notas, string $motivo): void
    {
        DB::transaction(function () use ($entrada, $salida, $notas, $motivo) {
            FichajeCorreccion::create([
                'fichaje_id' => $this->id,
                'entrada_anterior' => $this->entrada,
                'salida_anterior' => $this->salida,
                'entrada_nueva' => $entrada,
                'salida_nueva' => $salida,
                'motivo' => $motivo,
                'corregido_por' => Auth::id(),
            ]);

            $this->forceFill([
                'entrada' => $entrada,
                'salida' => $salida,
                'notas' => $notas,
                'edited_by' => Auth::id(),
                'edited_at' => now(),
            ])->save();
        });
    }

    /** Alta manual de un fichaje para otro empleado (olvidó fichar): también queda como corrección, sin valor anterior. */
    public static function registrarManual(int $userId, string $entrada, ?string $salida, ?string $notas, string $motivo, ?string $ip): self
    {
        return DB::transaction(function () use ($userId, $entrada, $salida, $notas, $motivo, $ip) {
            $fichaje = self::create(['user_id' => $userId, 'entrada' => $entrada, 'salida' => $salida, 'notas' => $notas, 'ip_address' => $ip]);

            FichajeCorreccion::create([
                'fichaje_id' => $fichaje->id,
                'entrada_nueva' => $entrada,
                'salida_nueva' => $salida,
                'motivo' => $motivo,
                'corregido_por' => Auth::id(),
            ]);

            return $fichaje;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
