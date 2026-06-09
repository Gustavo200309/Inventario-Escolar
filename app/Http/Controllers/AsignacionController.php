<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\HistorialAsignacion;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AsignacionController extends Controller
{
    private const TIPOS_MOVIMIENTO = 'Asignacion,Transferencia,Devolucion,Reasignacion,Cambio de area';

    public function index(Request $request): View
    {
        $search = $request->query('search');

        $asignaciones = Bien::with([
            'area',
            'personal',
            'ultimoHistorial.personalAnterior',
            'ultimoHistorial.personalNuevo',
            'ultimoHistorial.areaAnterior',
            'ultimoHistorial.areaNueva',
        ])
            ->when($search, fn($query) => $query->where(fn($query) =>
                $query->where('nombre_bien', 'like', "%{$search}%")
                    ->orWhere('serie', 'like', "%{$search}%")
                    ->orWhere('no_inventario', 'like', "%{$search}%")
                    ->orWhere('codigo_barras', 'like', "%{$search}%")
                    ->orWhereHas('personal', fn($query) => $query->where('nombre', 'like', "%{$search}%"))
                    ->orWhereHas('area', fn($query) => $query->where('nombre_area', 'like', "%{$search}%"))
            ))
            ->orderBy('fecha_registro', 'desc')
            ->get();

        return view('admin.asignaciones', [
            'asignaciones' => $asignaciones,
            'bienes' => Bien::with(['area', 'personal'])->orderBy('nombre_bien')->get(),
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
            'bienes' => Bien::with(['area', 'personal'])->orderBy('nombre_bien')->get(),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $this->validateMovimiento($request);
        $bien = Bien::findOrFail($data['id_bien']);

        $this->registrarMovimiento($bien, $data, 'Movimiento registrado');

        return redirect()->route('admin.asignaciones')->with('success', 'Movimiento registrado correctamente.');
    }

    public function update(Request $request, Bien $bien)
    {
        $this->authorizeAdmin();

        $data = $this->validateMovimiento($request);
        $this->registrarMovimiento($bien, $data, 'Movimiento actualizado');

        return redirect()->route('admin.asignaciones')->with('success', 'Movimiento actualizado correctamente.');
    }

    private function validateMovimiento(Request $request): array
    {
        return $request->validate([
            'id_bien' => ['required', 'integer', 'exists:bienes,id_bien'],
            'id_personal_nuevo' => ['nullable', 'integer', 'exists:personal,id_personal'],
            'id_area_nueva' => ['nullable', 'integer', 'exists:areas,id_area'],
            'tipo_movimiento' => ['required', 'in:' . self::TIPOS_MOVIMIENTO],
            'observaciones' => ['nullable', 'string'],
        ]);
    }

    private function registrarMovimiento(Bien $bien, array $data, string $observacionDefault): void
    {
        $esDevolucion = $data['tipo_movimiento'] === 'Devolucion';
        $nuevoPersonal = $esDevolucion ? null : ($data['id_personal_nuevo'] ?? $bien->id_personal);
        $nuevaArea = $esDevolucion ? null : ($data['id_area_nueva'] ?? $bien->id_area);

        HistorialAsignacion::create([
            'id_bien' => $bien->id_bien,
            'id_personal_anterior' => $bien->id_personal,
            'id_personal_nuevo' => $nuevoPersonal,
            'id_area_anterior' => $bien->id_area,
            'id_area_nueva' => $nuevaArea,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => $data['tipo_movimiento'],
            'observaciones' => $data['observaciones'] ?? $observacionDefault,
        ]);

        $bien->update([
            'id_personal' => $nuevoPersonal,
            'id_area' => $nuevaArea,
            'estatus' => $esDevolucion ? 'Disponible' : 'Asignado',
        ]);
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Acceso denegado.');
        }
    }
}
