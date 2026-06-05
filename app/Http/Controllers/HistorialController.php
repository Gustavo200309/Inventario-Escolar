<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\HistorialAsignacion;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HistorialController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $tipo = $request->query('tipo');

        $historiales = HistorialAsignacion::with(['bien', 'personalAnterior', 'personalNuevo', 'areaAnterior', 'areaNueva'])
            ->when($search, fn($query) => $query->whereHas('bien', fn($query) => $query->where('nombre_bien', 'like', "%{$search}%"))
                ->orWhere('tipo_movimiento', 'like', "%{$search}%")
                ->orWhere('observaciones', 'like', "%{$search}%")
            )
            ->when($tipo && $tipo !== 'Todos', fn($query) => $query->where('tipo_movimiento', $tipo))
            ->orderBy('fecha_movimiento', 'desc')
            ->get();

        return view('admin.historial', [
            'historiales' => $historiales,
            'search' => $search,
            'tipo' => $tipo,
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.historial-create', [
            'bienes' => Bien::with(['area', 'personal'])->orderBy('nombre_bien')->get(),
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
            'tipo_movimiento' => ['required', 'in:Asignacion,Reasignacion,Cambio de area'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $bien = Bien::findOrFail($data['id_bien']);

        $historial = HistorialAsignacion::create([
            'id_bien' => $bien->id_bien,
            'id_personal_anterior' => $bien->id_personal,
            'id_personal_nuevo' => $data['id_personal_nuevo'] ?? $bien->id_personal,
            'id_area_anterior' => $bien->id_area,
            'id_area_nueva' => $data['id_area_nueva'] ?? $bien->id_area,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => $data['tipo_movimiento'],
            'observaciones' => $data['observaciones'] ?? 'Movimiento registrado',
        ]);

        $bien->update([
            'id_personal' => $historial->id_personal_nuevo,
            'id_area' => $historial->id_area_nueva,
        ]);

        return redirect()->route('admin.historial')->with('success', 'Movimiento registrado correctamente.');
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Acceso denegado.');
        }
    }
}
