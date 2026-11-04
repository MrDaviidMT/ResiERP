<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * El autor de un registro clínico se toma siempre del usuario autenticado al crearlo.
 * user_id no es asignable desde formularios: así no se puede registrar nada en nombre de otro.
 */
trait TieneAutor
{
    public static function bootTieneAutor(): void
    {
        static::creating(function (self $registro) {
            $registro->user_id ??= Auth::id();
        });
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function esAutor(User $user): bool
    {
        return $this->user_id !== null && $this->user_id === $user->id;
    }
}
