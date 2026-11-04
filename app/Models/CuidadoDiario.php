<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use App\Models\Concerns\TieneAutor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuidadoDiario extends Model
{
    use Anulable, HasFactory, TieneAutor;

    protected $table = 'cuidados_diarios';

    protected $fillable = [
        'residente_id',
        'fecha',
        'comio_bien',
        'se_baño',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'comio_bien' => 'boolean',
        'se_baño' => 'boolean',
    ];

    public function residente(): BelongsTo
    {
        return $this->belongsTo(Residente::class);
    }
}
