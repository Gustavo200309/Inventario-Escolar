<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $usuarios = [];

        if ($user && $user->isAdmin()) {
            $usuarios = User::orderBy('created_at', 'desc')->get();
        }

        return view('admin.configuracion', [
            'users' => $usuarios,
        ]);
    }

    public function destroy(User $usuario)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Solo administradores pueden eliminar usuarios.');
        }

        if ($user->id === $usuario->id) {
            return Redirect::route('admin.configuracion')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $usuario->delete();

        return Redirect::route('admin.configuracion')->with('success', 'Usuario eliminado correctamente.');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Solo administradores pueden crear usuarios.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:admin,visualizador'],
        ]);

        $usuario = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return Redirect::route('admin.configuracion')->with('success', 'Usuario creado correctamente.');
    }
}
