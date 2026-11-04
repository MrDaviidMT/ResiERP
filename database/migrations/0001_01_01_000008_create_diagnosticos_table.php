<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residente_id');
            $table->foreign('residente_id')->references('id')->on('residentes')->onDelete('cascade');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('tipo', 50)->default('cronico');
            $table->date('fecha_diagnostico');
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->text('tratamiento')->nullable();
            $table->string('medico')->nullable();
            $table->string('especialidad')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosticos');
    }
};
