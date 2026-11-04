<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cada anotación clínica identifica al profesional que la hizo.
// Nulo en los registros anteriores a esta migración (su autor solo consta en la auditoría).
// restrictOnDelete: no se puede borrar un usuario con registros clínicos (los usuarios se desactivan).
return new class extends Migration
{
    private const TABLAS = ['diagnosticos', 'alergias', 'constantes_vitales', 'visitas_medicas', 'medicaciones'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('residente_id')->constrained()->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
