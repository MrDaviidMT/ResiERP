<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_residente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->unique(['actividad_id', 'residente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_residente');
    }
};
