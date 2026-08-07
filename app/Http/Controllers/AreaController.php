<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AreaController extends Controller
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

        $areas = Area::withCount(['bienes', 'personal'])
            ->when($search, fn($query) => $query->where('nombre_area', 'like', "%{$search}%"))
            ->orderBy('nombre_area')
            ->get();

        return view('admin.areas', [
            'areas' => $areas,
            'search' => $search,
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.areas-create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'nombre_area' => ['required', 'string', 'min:2', 'max:150', Rule::unique('areas', 'nombre_area')],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estatus' => ['required', 'in:Activa,Inactiva'],
        ]);

        Area::create(array_merge($data, [
            'fecha_registro' => now(),
        ]));

        return redirect()->route('admin.areas')->with('success', 'Área registrada correctamente.');
    }

    public function show(Area $area): View
    {
        return view('admin.areas-show', [
            'area' => $area->load(['bienes', 'personal']),
        ]);
    }

    public function edit(Area $area): View
    {
        $this->authorizeAdmin();

        return view('admin.areas-edit', [
            'area' => $area,
        ]);
    }

    public function update(Request $request, Area $area)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'nombre_area' => ['required', 'string', 'min:2', 'max:150', Rule::unique('areas', 'nombre_area')->ignore($area->id_area, 'id_area')],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estatus' => ['required', 'in:Activa,Inactiva'],
        ]);

        $area->update($data);

        return redirect()->route('admin.areas')->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area)
    {
        $this->authorizeAdmin();

        // La clave foranea deja el bien sin area; si ademas queda sin responsable,
        // no puede seguir marcado como "Asignado".
        $afectados = Bien::withEliminados()->where('id_area', $area->id_area)->pluck('id_bien');

        $area->delete();

        if ($afectados->isNotEmpty()) {
            Bien::withEliminados()
                ->whereIn('id_bien', $afectados)
                ->whereNull('id_area')
                ->whereNull('id_personal')
                ->where('estatus', 'Asignado')
                ->update(['estatus' => 'Disponible']);
        }

        return redirect()->route('admin.areas')->with('success', 'Área eliminada correctamente.');
    }
}
