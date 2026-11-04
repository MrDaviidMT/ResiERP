<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Política de conservación: marca para no volver a anonimizar lo ya anonimizado
return new class extends Migration
{
    public function up(): void
    {
        foreach (['residentes', 'users'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->timestamp('anonimizado_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['residentes', 'users'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('anonimizado_at');
            });
        }
    }
};
