<?php

namespace Tests\Feature\Seguridad;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

// El disco privado no debe exponer rutas de descarga ni de subida
class AlmacenamientoPrivadoTest extends TestCase
{
    public function test_the_private_disk_does_not_publish_storage_routes(): void
    {
        $rutas = collect(Route::getRoutes())->map(fn ($ruta) => $ruta->methods()[0].' '.$ruta->uri());

        $this->assertFalse($rutas->contains(fn (string $r) => str_contains($r, 'storage/{path}')),
            'El disco privado está publicando rutas /storage/{path}');
    }

    public function test_clinical_pdfs_are_only_reachable_through_the_controller(): void
    {
        $this->get('/storage/informes/cualquiera.pdf')->assertNotFound();
    }
}
