<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// El rol vive ya solo en las tablas de spatie/laravel-permission: una única fuente de verdad
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('enfermero');
        });

        $roles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_type', 'App\\Models\\User')
            ->pluck('roles.name', 'model_id');

        foreach ($roles as $userId => $rol) {
            DB::table('users')->where('id', $userId)->update(['role' => $rol === 'superadmin' ? 'admin' : $rol]);
        }
    }
};
