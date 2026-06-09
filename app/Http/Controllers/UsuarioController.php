<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        return view('admin.usuarios', [
            'users' => User::orderBy('created_at', 'desc')->get(),
            'isAdmin' => $user && method_exists($user, 'isAdmin') && $user->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

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

        return redirect()->route('admin.usuarios')->with('success', 'Usuario creado correctamente.');
    }

    public function destroy(User $usuario)
    {
        $this->authorizeAdmin();

        if (Auth::id() === $usuario->id) {
            return redirect()->route('admin.usuarios')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $usuario->delete();

        return redirect()->route('admin.usuarios')->with('success', 'Usuario eliminado correctamente.');
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Solo administradores pueden gestionar usuarios.');
        }
    }
}
