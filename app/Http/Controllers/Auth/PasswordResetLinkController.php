<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Solo las cuentas activas reciben enlace (el gestor busca al usuario con estas credenciales).
        // La respuesta es siempre la misma, exista o no la cuenta: así no se puede averiguar qué
        // emails están registrados. Tampoco se muestra el aviso de espera, que solo aparece si existe.
        Password::sendResetLink(['email' => $request->input('email'), 'activo' => true]);

        return back()->with('status', __('passwords.sent_generic'));
    }
}
