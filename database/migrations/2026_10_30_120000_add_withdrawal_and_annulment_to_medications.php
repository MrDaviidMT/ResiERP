<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Una pauta se RETIRA (decisión clínica, queda en el historial) o se ANULA (registrada por error)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicaciones', function (Blueprint $table) {
            $table->timestamp('fecha_fin')->nullable();
            $table->foreignId('retirado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('motivo_retirada')->nullable();
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('motivo_anulacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('medicaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('retirado_por');
            $table->dropConstrainedForeignId('anulado_por');
            $table->dropColumn(['fecha_fin', 'motivo_retirada', 'anulado_at', 'motivo_anulacion']);
        });
    }
};
