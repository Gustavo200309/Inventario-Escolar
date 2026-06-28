<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        return view('admin.usuarios', [
            'users' => $usuarios,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin('Solo administradores pueden crear usuarios.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:admin,visualizador'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return Redirect::route('admin.usuarios')->with('success', 'Usuario creado correctamente.');
    }

    public function destroy(User $usuario)
    {
        $this->authorizeAdmin('Solo administradores pueden eliminar usuarios.');

        if (Auth::id() === $usuario->id) {
            return Redirect::route('admin.usuarios')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $usuario->delete();

        return Redirect::route('admin.usuarios')->with('success', 'Usuario eliminado correctamente.');
    }

    private function authorizeAdmin(string $message): void
    {
        $user = Auth::user();

        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, $message);
        }
    }
}
