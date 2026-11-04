<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Familiar extends Model
{
    use HasFactory;

    protected $table = 'familiares';

    protected $fillable = [
        'nombre',
        'apellidos',
        'dni',
        'telefono',
        'email',
        'direccion',
    ];

    public function residentes(): BelongsToMany
    {
        return $this->belongsToMany(Residente::class, 'familiar_residente')
            ->withPivot(['parentesco', 'es_emergencia', 'autorizado', 'observaciones'])
            ->withTimestamps();
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellidos}";
    }
}
