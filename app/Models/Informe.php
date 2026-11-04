<?php

namespace App\Models;

use App\Enums\Permission;
use App\Models\Concerns\Anulable;
use App\Models\Concerns\TieneAutor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Informe extends Model
{
    use Anulable, HasFactory, TieneAutor;

    public const TIPOS = [
        'psicologico' => 'Psicológico',
        'social' => 'Social',
        'fisioterapia' => 'Fisioterapia',
        'enfermeria' => 'Enfermería',
        'medico' => 'Médico',
        'general' => 'General',
    ];

    protected $fillable = [
        'titulo',
        'tipo',
        'contenido',
        'pdf_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residentes(): BelongsToMany
    {
        return $this->belongsToMany(Residente::class, 'informe_residente');
    }

    /** Tipos de informe que el usuario puede leer (informes.ver.{tipo}). @return list<string> */
    public static function tiposVisiblesPara(User $user): array
    {
        return array_values(array_filter(array_keys(self::TIPOS), fn (string $tipo) => $user->can(Permission::verInforme($tipo))));
    }

    /** Tipos de informe que el usuario puede crear (informes.crear.{tipo}). @return array<string, string> */
    public static function tiposCreablesPor(User $user): array
    {
        return array_filter(self::TIPOS, fn (string $tipo) => $user->can(Permission::crearInforme($tipo)), ARRAY_FILTER_USE_KEY);
    }

    public function scopeVisiblesPara(Builder $query, User $user): void
    {
        $query->whereIn('tipo', self::tiposVisiblesPara($user));
    }
}
