<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Estatuto de los Trabajadores art. 34.9: el registro de jornada debe ser fiable y trazable.
// - fichaje_correcciones: historial de solo inserción de cada corrección (valor anterior, nuevo, motivo, quién)
// - fichajes: se anulan con motivo en lugar de borrarse
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichaje_correcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fichaje_id')->constrained('fichajes')->restrictOnDelete();
            // Nulos en el alta manual de un fichaje (no había valor anterior)
            $table->timestamp('entrada_anterior')->nullable();
            $table->timestamp('salida_anterior')->nullable();
            $table->timestamp('entrada_nueva');
            $table->timestamp('salida_nueva')->nullable();
            $table->text('motivo');
            $table->foreignId('corregido_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('fichajes', function (Blueprint $table) {
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('motivo_anulacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fichajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulado_por');
            $table->dropColumn(['anulado_at', 'motivo_anulacion']);
        });

        Schema::dropIfExists('fichaje_correcciones');
    }
};
