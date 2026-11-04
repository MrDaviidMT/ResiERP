<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residente_id');
            $table->foreign('residente_id')->references('id')->on('residentes')->onDelete('cascade');
            $table->unsignedBigInteger('familiar_id')->nullable();
            $table->foreign('familiar_id')->references('id')->on('familiares')->nullOnDelete();
            $table->string('nombre_visitante');
            $table->string('documento', 20)->nullable();
            $table->dateTime('entrada');
            $table->dateTime('salida')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};
