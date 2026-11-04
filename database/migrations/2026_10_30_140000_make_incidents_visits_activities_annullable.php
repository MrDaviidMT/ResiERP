<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Incidencias (valor probatorio), visitas (control de accesos) y actividades
// (si un residente participó, forma parte de su atención) se anulan con motivo en lugar de borrarse
return new class extends Migration
{
    private const TABLAS = ['incidencias', 'visitas', 'actividades'];

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
