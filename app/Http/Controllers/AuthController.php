<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): RedirectResponse|View
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'usuario' => ['required', 'string', 'max:255', 'email:rfc,dns'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // Convertir 'usuario' a 'email' para el intento de login
        $loginCredentials = [
            'email' => $credentials['usuario'],
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($loginCredentials, $request->boolean('remember'))) {
            return back()->withErrors(['usuario' => 'El usuario o la contraseña son incorrectos.'])->onlyInput('usuario');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
