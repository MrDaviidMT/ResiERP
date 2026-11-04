<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La base de datos impide borrar algo que tenga historial (última línea de defensa).
 * Borrar un residente, una habitación o un usuario ya no arrastra su documentación clínica,
 * sus incidencias ni su registro de jornada.
 */
return new class extends Migration
{
    /** [tabla, columna, tabla referenciada, comportamiento anterior (para down)] */
    private const CLAVES = [
        ['medicaciones', 'residente_id', 'residentes', 'cascade'],
        ['diagnosticos', 'residente_id', 'residentes', 'cascade'],
        ['constantes_vitales', 'residente_id', 'residentes', 'cascade'],
        ['visitas_medicas', 'residente_id', 'residentes', 'cascade'],
        ['alergias', 'residente_id', 'residentes', 'cascade'],
        ['cuidados_diarios', 'residente_id', 'residentes', 'cascade'],
        ['dietas', 'residente_id', 'residentes', 'cascade'],
        ['visitas', 'residente_id', 'residentes', 'cascade'],
        ['incidencias', 'residente_id', 'residentes', 'cascade'],
        ['incidencias', 'habitacion_id', 'habitaciones', 'cascade'],
        ['incidencias', 'user_id', 'users', 'set null'],
        ['familiar_residente', 'familiar_id', 'familiares', 'cascade'],
        ['familiar_residente', 'residente_id', 'residentes', 'cascade'],
        ['informe_residente', 'informe_id', 'informes', 'cascade'],
        ['informe_residente', 'residente_id', 'residentes', 'cascade'],
        ['actividad_residente', 'actividad_id', 'actividades', 'cascade'],
        ['actividad_residente', 'residente_id', 'residentes', 'cascade'],
        ['fichajes', 'user_id', 'users', 'cascade'],
        ['fichajes', 'edited_by', 'users', 'set null'],
        ['logs', 'user_id', 'users', 'set null'],
    ];

    public function up(): void
    {
        foreach (self::CLAVES as [$tabla, $columna, $referencia]) {
            Schema::table($tabla, function (Blueprint $table) use ($columna, $referencia) {
                $table->dropForeign([$columna]);
                $table->foreign($columna)->references('id')->on($referencia)->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::CLAVES as [$tabla, $columna, $referencia, $anterior]) {
            Schema::table($tabla, function (Blueprint $table) use ($columna, $referencia, $anterior) {
                $table->dropForeign([$columna]);
                $table->foreign($columna)->references('id')->on($referencia)->onDelete($anterior);
            });
        }
    }
};
