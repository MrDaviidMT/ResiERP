<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residente_id');
            $table->foreign('residente_id')->references('id')->on('residentes')->onDelete('cascade');
            $table->string('medicamento');
            $table->string('dosis');
            $table->boolean('desayuno')->default(false);
            $table->boolean('comida')->default(false);
            $table->boolean('cena')->default(false);
            $table->boolean('noche')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicaciones');
    }
};
