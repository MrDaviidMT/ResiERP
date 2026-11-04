<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alergias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->string('alergeno');
            $table->text('reaccion')->nullable();
            $table->enum('severidad', ['leve', 'moderada', 'grave'])->default('leve');
            $table->date('fecha_deteccion')->nullable();
            $table->date('fecha_resolucion')->nullable();
            $table->boolean('activa')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alergias');
    }
};
