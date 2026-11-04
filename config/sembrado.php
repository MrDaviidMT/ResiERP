<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contraseñas del sembrado inicial
    |--------------------------------------------------------------------------
    |
    | Están aquí, y no se leen con env() desde el seeder, porque el entrypoint del
    | contenedor ejecuta `php artisan optimize`: con la configuración cacheada, Laravel
    | deja de cargar el .env y env() devuelve null en cualquier sitio que no sea este
    | directorio. Regla general de Laravel: env() solo dentro de config/.
    |
    */

    'admin_password' => env('ADMIN_PASSWORD'),

    'demo_password' => env('DEMO_PASSWORD'),

];
