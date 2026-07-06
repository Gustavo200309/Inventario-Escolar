<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarcaController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Acceso denegado.');
        }
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'nombre_marca' => ['required', 'string', 'max:100', 'unique:marcas,nombre_marca'],
        ]);

        $marca = Marca::create($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'id_marca' => $marca->id_marca,
                'nombre_marca' => $marca->nombre_marca,
            ]);
        }

        return redirect()->back()->with('success', 'Marca agregada correctamente.');
    }
}
