<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familiar_residente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('familiar_id')->constrained('familiares')->cascadeOnDelete();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->string('parentesco', 100);
            $table->boolean('es_emergencia')->default(false);
            $table->boolean('autorizado')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['familiar_id', 'residente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('familiar_residente');
    }
};
