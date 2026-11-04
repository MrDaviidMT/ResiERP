<?php

namespace Tests\Feature\Permisos;

use App\Enums\Role;
use App\Models\Residente;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// `php artisan migrate --seed` está documentado en el proyecto: repetirlo no debe fallar ni duplicar datos
class SeederIdempotenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_the_full_seeder_twice_does_not_duplicate_anything(): void
    {
        $this->seed(DatabaseSeeder::class);
        $usuarios = User::count();
        $residentes = Residente::count();
        $contrasena = User::where('email', 'admin@residencia.test')->value('password');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($usuarios, User::count());
        $this->assertSame($residentes, Residente::count());
        // Volver a sembrar no cambia la contraseña de una cuenta existente
        $this->assertSame($contrasena, User::where('email', 'admin@residencia.test')->value('password'));
        $this->assertTrue(User::where('email', 'enfermero@residencia.test')->first()->hasRole(Role::Enfermero));
    }
}
