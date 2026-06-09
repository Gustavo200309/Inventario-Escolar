<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\HistorialAsignacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PendientesController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $prioridad = $request->query('prioridad');

        $pendientes = Bien::with(['area', 'personal'])
            ->where(function ($query) {
                $query->whereIn('estatus', ['Pendiente', 'En revision', 'En mantenimiento', 'Danado'])
                    ->orWhere('estatus', 'like', '%revisi%')
                    ->orWhere('estatus', 'like', 'Da%ado')
                    ->orWhereNull('id_personal')
                    ->orWhereNull('id_area');
            })
            ->when($search, fn($query) => $query->where(function ($query) use ($search) {
                $query->where('nombre_bien', 'like', "%{$search}%")
                    ->orWhere('no_inventario', 'like', "%{$search}%")
                    ->orWhere('codigo_barras', 'like', "%{$search}%")
                    ->orWhere('serie', 'like', "%{$search}%");
            }))
            ->orderBy('fecha_registro', 'desc')
            ->get()
            ->map(function (Bien $bien) {
                $this->clasificarPendiente($bien);

                return $bien;
            })
            ->when($prioridad, fn($items) => $items->where('prioridad', $prioridad)->values());

        return view('admin.pendientes', [
            'pendientes' => $pendientes,
            'search' => $search,
            'prioridad' => $prioridad,
        ]);
    }

    public function resolver(Request $request, Bien $bien)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'accion' => ['required', 'in:Asignar,Mantenimiento,Reparar,Descartar'],
            'notas' => ['nullable', 'string'],
            'nuevo_estatus' => ['required', 'in:Resuelto,En revision,En mantenimiento,Disponible,Baja'],
        ]);

        $estatus = match ($data['nuevo_estatus']) {
            'Resuelto' => $bien->id_personal || $bien->id_area ? 'Asignado' : 'Disponible',
            default => $data['nuevo_estatus'],
        };

        $bien->update([
            'estatus' => $estatus,
        ]);

        HistorialAsignacion::create([
            'id_bien' => $bien->id_bien,
            'id_personal_anterior' => $bien->id_personal,
            'id_personal_nuevo' => $bien->id_personal,
            'id_area_anterior' => $bien->id_area,
            'id_area_nueva' => $bien->id_area,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => 'Resolucion',
            'observaciones' => trim($data['accion'] . ': ' . ($data['notas'] ?? 'Pendiente resuelto')),
        ]);

        return redirect()->route('admin.pendientes')->with('success', 'Pendiente resuelto correctamente.');
    }

    private function clasificarPendiente(Bien $bien): void
    {
        if ($bien->estatus === 'Danado' || str_starts_with((string) $bien->estatus, 'Da')) {
            $bien->razon = 'Requiere revision fisica';
            $bien->prioridad = 'Alta';

            return;
        }

        if (! $bien->id_personal) {
            $bien->razon = 'Sin asignar';
            $bien->prioridad = 'Alta';

            return;
        }

        if (! $bien->id_area) {
            $bien->razon = 'Sin area';
            $bien->prioridad = 'Media';

            return;
        }

        $bien->razon = $bien->estatus ?: 'Pendiente';
        $bien->prioridad = $bien->estatus === 'Pendiente' ? 'Media' : 'Baja';
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Acceso denegado.');
        }
    }
}
