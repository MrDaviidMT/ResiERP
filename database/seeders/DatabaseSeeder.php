<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Habitacion;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        // Idempotente: `migrate --seed` se puede repetir sin duplicar usuarios ni datos de demostración
        $this->usuario('admin@residencia.test', 'Administrador', Role::Superadmin->value, $this->contrasena('admin_password', 'ADMIN_PASSWORD'));

        $demo = $this->contrasena('demo_password', 'DEMO_PASSWORD');
        $roles = ['director', 'enfermero', 'auxiliar', 'recepcionista', 'trabajador_social', 'psicologo', 'cocinero', 'mantenimiento', 'fisioterapeuta'];
        foreach ($roles as $role) {
            $this->usuario("{$role}@residencia.test", ucfirst(str_replace('_', ' ', $role)), $role, $demo);
        }

        // Los datos de demostración se crean una sola vez
        if (Residente::withTrashed()->exists()) {
            return;
        }

        Habitacion::factory(10)->create();

        for ($i = 0; $i < 15; $i++) {
            $habitacion = Habitacion::where('estado', '!=', 'mantenimiento')
                ->withCount('residentes')
                ->get()
                ->filter(fn ($h) => $h->residentes_count < ($h->tipo === 'doble' ? 2 : 1))
                ->first();

            $residente = Residente::factory()->create([
                'habitacion_id' => $habitacion?->id,
            ]);

            if ($habitacion) {
                $habitacion->sincronizarEstado();
            }
        }
    }

    /**
     * Contraseña del sembrado, desde `config/sembrado.php` y nunca desde env(): con la
     * configuración cacheada (`php artisan optimize`, que ejecuta el entrypoint) el .env
     * ya no se lee y env() devolvería null.
     *
     * Sin respaldo aleatorio a propósito: antes se usaba Str::random(16), así que al faltar
     * la variable el despliegue "funcionaba" pero nadie podía iniciar sesión. Es mejor
     * detenerse y decir qué falta.
     */
    private function contrasena(string $clave, string $variable): string
    {
        $valor = config("sembrado.{$clave}");

        if (blank($valor)) {
            throw new RuntimeException(
                "Falta {$variable}: sin ella el sembrado crearía usuarios a los que nadie puede acceder. "
                .'Defínela en el .env antes de ejecutar los seeders.'
            );
        }

        return $valor;
    }

    /** Crea el usuario si no existe; si ya existe, solo asegura su rol (no cambia la contraseña). */
    private function usuario(string $email, string $nombre, string $rol, string $password): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $nombre, 'password' => Hash::make($password), 'email_verified_at' => now()],
        );

        if (! $user->hasRole($rol)) {
            $user->assignRole($rol);
        }
    }
}
