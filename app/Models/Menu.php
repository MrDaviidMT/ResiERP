<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'desayuno',
        'comida',
        'merienda',
        'cena',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
