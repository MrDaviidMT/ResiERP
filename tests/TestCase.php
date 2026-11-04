<?php

namespace Tests;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Los tests con RefreshDatabase empiezan con el catálogo de permisos y las plantillas de rol cargados
    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;
}
