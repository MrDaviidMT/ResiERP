<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Residente extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const GRUPOS_SANGUINEOS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', '0+', '0-'];

    protected $fillable = [
        'nombre',
        'apellidos',
        'dni',
        'nacimiento',
        'ingreso',
        'habitacion_id',
        'grupo_sanguineo',
        'fecha_defuncion',
        'dependencia',
    ];

    protected $casts = [
        'nacimiento' => 'date',
        'ingreso' => 'date',
        'fecha_defuncion' => 'date',
        'dependencia' => 'string',
    ];

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_id');
    }

    /** Pautas vigentes (no retiradas ni anuladas): lo que se administra hoy. */
    public function medicaciones()
    {
        return $this->hasMany(Medicacion::class, 'residente_id')->vigentes();
    }

    /** Historial de pautas retiradas. */
    public function medicacionesRetiradas()
    {
        return $this->hasMany(Medicacion::class, 'residente_id')->retiradas()->latest('fecha_fin');
    }

    public function incidencias()
    {
        return $this->hasMany(Incidencia::class, 'residente_id');
    }

    public function diagnosticos()
    {
        return $this->hasMany(Diagnostico::class);
    }

    public function alergias()
    {
        return $this->hasMany(Alergia::class);
    }

    public function constantes()
    {
        return $this->hasMany(ConstanteVital::class);
    }

    public function cuidadosDiarios()
    {
        return $this->hasMany(CuidadoDiario::class);
    }

    public function informes()
    {
        return $this->belongsToMany(Informe::class, 'informe_residente');
    }

    public function actividades()
    {
        return $this->belongsToMany(Actividad::class, 'actividad_residente');
    }

    public function visitasMedicas()
    {
        return $this->hasMany(VisitaMedica::class);
    }

    /** Familiares autorizados a visitar al residente (D9/D11): los únicos a los que se puede atribuir una visita. */
    public function familiaresAutorizados()
    {
        return $this->familiares()->wherePivot('autorizado', true);
    }

    public function familiares()
    {
        return $this->belongsToMany(Familiar::class, 'familiar_residente')
            ->withPivot(['parentesco', 'es_emergencia', 'autorizado', 'observaciones'])
            ->withTimestamps();
    }

    public function dieta()
    {
        return $this->hasOne(Dieta::class);
    }

    public function visitas()
    {
        return $this->hasMany(Visita::class);
    }
}
