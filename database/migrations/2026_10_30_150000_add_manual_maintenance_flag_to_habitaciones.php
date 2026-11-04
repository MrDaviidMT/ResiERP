<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Distingue por qué una habitación está en mantenimiento: a mano (no se libera sola)
// o por incidencias graves de infraestructura (se libera al resolverlas o anularlas)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habitaciones', function (Blueprint $table) {
            $table->boolean('mantenimiento_manual')->default(false)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('habitaciones', function (Blueprint $table) {
            $table->dropColumn('mantenimiento_manual');
        });
    }
};
