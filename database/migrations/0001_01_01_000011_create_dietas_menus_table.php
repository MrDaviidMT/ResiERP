<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dietas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('residente_id');
            $table->foreign('residente_id')->references('id')->on('residentes')->onDelete('cascade');
            $table->enum('tipo', ['normal', 'diabetica', 'hipocalorica', 'sin_sal', 'triturada', 'blanda', 'renal', 'otras'])->default('normal');
            $table->text('alergias_alimentarias')->nullable();
            $table->text('preferencias')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('desayuno')->nullable();
            $table->string('comida')->nullable();
            $table->string('merienda')->nullable();
            $table->string('cena')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
        Schema::dropIfExists('dietas');
    }
};
