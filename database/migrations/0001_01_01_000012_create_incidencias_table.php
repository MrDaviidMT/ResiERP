<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habitacion_id')->nullable()->constrained('habitaciones')->onDelete('cascade');
            $table->foreignId('residente_id')->nullable()->constrained('residentes')->onDelete('cascade');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('categoria', ['sanitaria', 'infraestructura', 'limpieza', 'personal', 'otros'])->default('infraestructura');
            $table->enum('estado', ['pendiente', 'en_proceso', 'resuelta'])->default('pendiente');
            $table->enum('prioridad', ['leve', 'grave', 'urgente'])->default('leve');
            $table->timestamp('fecha_resolucion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
