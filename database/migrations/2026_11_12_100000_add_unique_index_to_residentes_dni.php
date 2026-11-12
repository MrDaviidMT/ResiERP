<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// La validación comprueba el DNI antes de guardar, pero dos altas simultáneas pasarían las dos
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residentes', function (Blueprint $table) {
            $table->unique('dni');
        });
    }

    public function down(): void
    {
        Schema::table('residentes', function (Blueprint $table) {
            $table->dropUnique(['dni']);
        });
    }
};
