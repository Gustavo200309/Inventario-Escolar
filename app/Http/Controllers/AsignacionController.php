<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\Personal;
use App\Models\HistorialAsignacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AsignacionController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $asignaciones = Bien::with(['area', 'personal'])
            ->when($search, fn($query) => $query->where(fn($query) =>
                $query->where('nombre_bien', 'like', "%{$search}%")
                    ->orWhere('serie', 'like', "%{$search}%")
                    ->orWhere('no_inventario', 'like', "%{$search}%")
            ))
            ->orderBy('fecha_registro', 'desc')
            ->get();

        return view('admin.asignaciones', [
            'asignaciones' => $asignaciones,
            'bienes' => Bien::orderBy('nombre_bien')->get(),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
            'search' => $search,
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.asignaciones-create', [
            'bienes' => Bien::orderBy('nombre_bien')->get(),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'id_bien' => ['required', 'integer', 'exists:bienes,id_bien'],
            'id_personal_nuevo' => ['nullable', 'integer', 'exists:personal,id_personal'],
            'id_area_nueva' => ['nullable', 'integer', 'exists:areas,id_area'],
            'tipo_movimiento' => ['required', 'in:Asignación,Transferencia,Devolución,Asignacion,Reasignacion,Cambio de area'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $bien = Bien::findOrFail($data['id_bien']);

        HistorialAsignacion::create([
            'id_bien' => $bien->id_bien,
            'id_personal_anterior' => $bien->id_personal,
            'id_personal_nuevo' => $data['id_personal_nuevo'] ?? $bien->id_personal,
            'id_area_anterior' => $bien->id_area,
            'id_area_nueva' => $data['id_area_nueva'] ?? $bien->id_area,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => $data['tipo_movimiento'],
            'observaciones' => $data['observaciones'] ?? 'Asignación registrada',
        ]);

        $bien->update([
            'id_personal' => $data['id_personal_nuevo'],
            'id_area' => $data['id_area_nueva'],
            'estatus' => 'Asignado',
        ]);

        return redirect()->route('admin.asignaciones')->with('success', 'Asignación registrada correctamente.');
    }

    public function update(Request $request, Bien $bien)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'id_bien' => ['required', 'integer', 'exists:bienes,id_bien'],
            'id_personal_nuevo' => ['nullable', 'integer', 'exists:personal,id_personal'],
            'id_area_nueva' => ['nullable', 'integer', 'exists:areas,id_area'],
            'tipo_movimiento' => ['required', 'in:Asignación,Transferencia,Devolución,Asignacion,Reasignacion,Cambio de area'],
            'observaciones' => ['nullable', 'string'],
        ]);

        HistorialAsignacion::create([
            'id_bien' => $bien->id_bien,
            'id_personal_anterior' => $bien->id_personal,
            'id_personal_nuevo' => $data['id_personal_nuevo'] ?? $bien->id_personal,
            'id_area_anterior' => $bien->id_area,
            'id_area_nueva' => $data['id_area_nueva'] ?? $bien->id_area,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => $data['tipo_movimiento'],
            'observaciones' => $data['observaciones'] ?? 'Asignación actualizada',
        ]);

        $bien->update([
            'id_personal' => $data['id_personal_nuevo'],
            'id_area' => $data['id_area_nueva'],
            'estatus' => 'Asignado',
        ]);

        return redirect()->route('admin.asignaciones')->with('success', 'Asignación actualizada correctamente.');
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Acceso denegado.');
        }
    }
}
