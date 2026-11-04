<?php

// Arranque de la aplicación: middleware global y manejador de excepciones de autorización

use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [__DIR__.'/../routes/web.php', __DIR__.'/../routes/auth.php'],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureUserIsActive::class,
        ]);

        // En todas las respuestas, también en las de error y en las descargas
        $middleware->append(CabecerasSeguridad::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes permiso para realizar esta acción.'], 403);
            }

            return redirect()->back()->with('error', 'No tienes permiso para realizar esta acción.');
        });
    })->create();
