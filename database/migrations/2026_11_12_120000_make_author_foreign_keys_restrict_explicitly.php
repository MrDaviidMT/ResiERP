<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estas claves se crearon sin acción al borrar. En MySQL eso ya equivale a RESTRICT,
 * pero dejarlo escrito hace que no dependa del motor y que todas las de autor se lean igual.
 */
return new class extends Migration
{
    private const TABLAS = ['actividades', 'cuidados_diarios', 'informes'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')->on('users');
            });
        }
    }
};
