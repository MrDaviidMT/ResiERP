<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fail-closed también en la base de datos: si un alta no indica la autorización, el familiar no queda autorizado.
// Solo cambia el valor por defecto; los vínculos existentes conservan su valor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('familiar_residente', function (Blueprint $table) {
            $table->boolean('autorizado')->default(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('familiar_residente', function (Blueprint $table) {
            $table->boolean('autorizado')->default(true)->change();
        });
    }
};
