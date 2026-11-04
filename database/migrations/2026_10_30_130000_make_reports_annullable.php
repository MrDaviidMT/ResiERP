<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Los informes (documentación clínica) no se borran, se anulan con motivo; su PDF se conserva
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informes', function (Blueprint $table) {
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('motivo_anulacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('informes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulado_por');
            $table->dropColumn(['anulado_at', 'motivo_anulacion']);
        });
    }
};
