<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residentes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('dni')->nullable();
            $table->date('nacimiento');
            $table->date('ingreso');
            $table->date('fecha_defuncion')->nullable();
            $table->foreignId('habitacion_id')->nullable()->constrained('habitaciones')->nullOnDelete();
            $table->string('grupo_sanguineo', 5)->nullable();
            $table->string('dependencia')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residentes');
    }
};
