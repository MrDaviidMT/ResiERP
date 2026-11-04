<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Acciones del sistema (tareas programadas) que no se refieren a un registro concreto,
// como la ejecución de la política de conservación
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->string('loggable_type')->nullable()->change();
            $table->unsignedBigInteger('loggable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->string('loggable_type')->nullable(false)->change();
            $table->unsignedBigInteger('loggable_id')->nullable(false)->change();
        });
    }
};
