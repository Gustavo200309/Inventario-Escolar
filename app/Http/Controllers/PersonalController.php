<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PersonalController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Acceso denegado.');
        }
    }

    public function index(Request $request): View
    {
        $search = $request->query('search');

        $personals = Personal::with('area')
            ->when($search, fn($query) => $query->where(fn($query) =>
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('puesto', 'like', "%{$search}%")
            ))
            ->orderBy('nombre')
            ->get();

        return view('admin.personal', [
            'personals' => $personals,
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
            'search' => $search,
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.personal-create', [
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'puesto' => ['required', 'string', 'max:100'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'id_area' => ['nullable', 'integer', 'exists:areas,id_area'],
            'estatus' => ['required', 'in:Activo,Inactivo'],
        ]);

        Personal::create(array_merge($data, [
            'fecha_registro' => now(),
        ]));

        return redirect()->route('admin.personal')->with('success', 'Personal registrado correctamente.');
    }

    public function show(Personal $personal): View
    {
        return view('admin.personal-show', [
            'personal' => $personal->load('area'),
        ]);
    }

    public function edit(Personal $personal): View
    {
        $this->authorizeAdmin();

        return view('admin.personal-edit', [
            'personal' => $personal,
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
        ]);
    }

    public function update(Request $request, Personal $personal)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'puesto' => ['required', 'string', 'max:100'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'id_area' => ['nullable', 'integer', 'exists:areas,id_area'],
            'estatus' => ['required', 'in:Activo,Inactivo'],
        ]);

        $personal->update($data);

        return redirect()->route('admin.personal')->with('success', 'Personal actualizado correctamente.');
    }

    public function destroy(Personal $personal)
    {
        $this->authorizeAdmin();

        $personal->delete();

        return redirect()->route('admin.personal')->with('success', 'Personal eliminado correctamente.');
    }
}
