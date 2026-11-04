<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El historial clínico no se borra, se anula con motivo
return new class extends Migration
{
    private const TABLAS = ['diagnosticos', 'alergias', 'constantes_vitales', 'cuidados_diarios', 'visitas_medicas'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->timestamp('anulado_at')->nullable();
                $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
                $table->text('motivo_anulacion')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('anulado_por');
                $table->dropColumn(['anulado_at', 'motivo_anulacion']);
            });
        }
    }
};
