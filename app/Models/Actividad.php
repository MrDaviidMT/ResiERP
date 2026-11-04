<?php

namespace App\Models;

use App\Models\Concerns\Anulable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Actividad extends Model
{
    use Anulable, HasFactory;

    protected $table = 'actividades';

    protected $fillable = [
        'user_id',
        'titulo',
        'descripcion',
        'fecha',
        'hora',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime:H:i',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residentes(): BelongsToMany
    {
        return $this->belongsToMany(Residente::class, 'actividad_residente');
    }
}
