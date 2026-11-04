<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informe_residente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('residente_id')->constrained()->cascadeOnDelete();
            $table->unique(['informe_id', 'residente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informe_residente');
    }
};
