<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas_medicas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residente_id');
            $table->foreign('residente_id')->references('id')->on('residentes')->onDelete('cascade');
            $table->string('medico');
            $table->string('especialidad')->nullable();
            $table->string('motivo');
            $table->dateTime('fecha_visita');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas_medicas');
    }
};
