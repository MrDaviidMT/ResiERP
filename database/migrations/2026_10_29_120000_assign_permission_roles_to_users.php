<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Pasa el rol de la columna users.role a las tablas de spatie/laravel-permission.
// El antiguo rol "admin" se convierte en "superadmin". Los permisos de cada rol los carga PermissionSeeder.
return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        foreach (DB::table('users')->select('id', 'role')->get() as $user) {
            $nombre = $user->role === 'admin' ? 'superadmin' : $user->role;

            DB::table('roles')->insertOrIgnore(['name' => $nombre, 'guard_name' => 'web', 'created_at' => $ahora, 'updated_at' => $ahora]);
            $rolId = DB::table('roles')->where('name', $nombre)->where('guard_name', 'web')->value('id');

            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $rolId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $user->id,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('model_has_roles')->where('model_type', 'App\\Models\\User')->delete();
    }
};
