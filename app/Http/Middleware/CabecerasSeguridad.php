<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad.
 *
 * Van en la aplicación para que se puedan comprobar con tests y sigan estando si cambia
 * el servidor web. Nginx solo las añade a los archivos que sirve él.
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        foreach ($this->cabeceras($request) as $nombre => $valor) {
            $respuesta->headers->set($nombre, $valor, false);
        }

        return $respuesta;
    }

    /** @return array<string, string> */
    private function cabeceras(Request $request): array
    {
        $cabeceras = [
            // Sin iframes: evita el secuestro de clics sobre la sesión abierta
            'X-Frame-Options' => 'DENY',
            // El navegador respeta el tipo declarado y no "adivina" (un .pdf no se ejecuta como script)
            'X-Content-Type-Options' => 'nosniff',
            // Al salir a otro sitio no se filtra la URL, que puede llevar el id de un residente
            'Referrer-Policy' => 'same-origin',
            // No hace falta ningún permiso del navegador
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Content-Security-Policy' => $this->csp(),
        ];

        // HSTS solo con HTTPS: anunciarlo por HTTP no sirve y puede dejar el sitio inaccesible
        if ($request->secure()) {
            $cabeceras['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $cabeceras;
    }

    private function csp(): string
    {
        return implode('; ', [
            "default-src 'self'",
            // 'unsafe-eval': Alpine.js compila sus expresiones con new Function(). La alternativa es
            // la variante @alpinejs/csp, que obliga a reescribir los componentes x-data.
            "script-src 'self' 'unsafe-eval'",
            // 'unsafe-inline': SweetAlert2 y Alpine aplican estilos en línea
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            // Ni peticiones ni websockets a terceros: los datos de salud no salen de aquí
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
