<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constantes_vitales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residente_id');
            $table->foreign('residente_id')->references('id')->on('residentes')->onDelete('cascade');
            $table->integer('tension_sistolica')->nullable();
            $table->integer('tension_diastolica')->nullable();
            $table->integer('frecuencia_cardiaca')->nullable();
            $table->decimal('temperatura', 4, 1)->nullable();
            $table->decimal('peso', 5, 2)->nullable();
            $table->integer('saturacion_oxigeno')->nullable();
            $table->decimal('glucosa', 5, 2)->nullable();
            $table->dateTime('fecha_registro');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constantes_vitales');
    }
};
