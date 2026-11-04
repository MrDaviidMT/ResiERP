<?php

namespace Tests\Feature\Seguridad;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * El sembrado inicial debe tomar las contraseñas de la configuración, no de env().
 *
 * El entrypoint del contenedor ejecuta `php artisan optimize`, que cachea la configuración;
 * a partir de ahí Laravel no lee el .env y `env()` devuelve null fuera de config/.
 */
class SembradoTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_users_use_the_configured_passwords(): void
    {
        config([
            'sembrado.admin_password' => 'ClaveDeAdministracion2026',
            'sembrado.demo_password' => 'ClaveDeDemostracion2026',
        ]);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@residencia.test')->firstOrFail();
        $this->assertTrue(
            Hash::check('ClaveDeAdministracion2026', $admin->password),
            'El administrador no se creó con la contraseña configurada.'
        );

        $demo = User::where('email', 'director@residencia.test')->firstOrFail();
        $this->assertTrue(
            Hash::check('ClaveDeDemostracion2026', $demo->password),
            'Los usuarios de demostración no se crearon con la contraseña configurada.'
        );
    }

    public function test_seeding_fails_loudly_when_a_password_is_missing(): void
    {
        config(['sembrado.admin_password' => null, 'sembrado.demo_password' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_PASSWORD');

        $this->seed(DatabaseSeeder::class);
    }
}
