<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Log;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $intended = $request->session()->pull('url.intended');

        $request->session()->regenerate();

        if ($intended) {
            $request->session()->put('url.intended', $intended);
        }

        Log::create([
            'user_id' => $request->user()->id,
            'loggable_type' => User::class,
            'loggable_id' => $request->user()->id,
            'action' => 'login',
            'changes' => null,
        ]);

        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('verification.notice'));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
