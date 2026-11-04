<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dieta extends Model
{
    use HasFactory;

    protected $fillable = [
        'residente_id',
        'tipo',
        'alergias_alimentarias',
        'preferencias',
        'observaciones',
    ];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
