<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\HistorialAsignacion;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PendientesController extends Controller
{
    /**
     * Cada accion de resolucion deja el bien en un unico estado valido.
     * El formulario y el backend usan este mismo mapa para evitar
     * combinaciones incoherentes (por ejemplo, "Mantenimiento" -> "Disponible").
     */
    public const ESTATUS_POR_ACCION = [
        'Asignar' => 'Asignado',
        'Mantenimiento' => 'En mantenimiento',
        'Reparar' => 'En revision',
        'Descartar' => 'Baja',
    ];

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $prioridad = $request->query('prioridad');
        $perPage = (int) $request->query('per_page', 25);
        $allowedPerPage = [10, 20, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }

        $baseQuery = Bien::with(['area', 'personal'])
            ->where(function ($query) {
                $query->whereIn('estatus', ['Pendiente', 'En revision', 'En mantenimiento', 'Danado'])
                    ->orWhere('estatus', 'like', '%revisi%')
                    ->orWhere('estatus', 'like', 'Da%')
                    ->orWhereNull('id_personal')
                    ->orWhereNull('id_area');
            })
            ->when($search, fn($query) => $query->where(function ($query) use ($search) {
                $query->where('nombre_bien', 'like', "%{$search}%")
                    ->orWhere('no_inventario', 'like', "%{$search}%")
                    ->orWhere('codigo_barras', 'like', "%{$search}%")
                    ->orWhere('serie', 'like', "%{$search}%");
            }));

        // Estadísticas rápidas (consultas separadas para ser precisas)
        $totalPendientes = (clone $baseQuery)->count();
        $prioridadAltaCount = (clone $baseQuery)->where(function ($q) {
            $q->where('estatus', 'Danado')
                ->orWhere('estatus', 'like', 'Da%')
                ->orWhereNull('id_personal');
        })->count();
        $sinAsignarCount = (clone $baseQuery)->whereNull('id_personal')->count();

        // Filtrado por prioridad (se traduce a condiciones SQL para paginar correctamente)
        if ($prioridad) {
            if ($prioridad === 'Alta') {
                $baseQuery = $baseQuery->where(function ($q) {
                    $q->where('estatus', 'Danado')
                        ->orWhere('estatus', 'like', 'Da%')
                        ->orWhereNull('id_personal');
                });
            } elseif ($prioridad === 'Media') {
                $baseQuery = $baseQuery->where(function ($q) {
                    $q->where('estatus', 'Pendiente')
                        ->orWhereNull('id_area');
                });
            } elseif ($prioridad === 'Baja') {
                $baseQuery = $baseQuery->whereNotNull('id_personal')
                    ->whereNotNull('id_area')
                    ->where('estatus', '<>', 'Pendiente')
                    ->where('estatus', 'not like', 'Da%')
                    ->where('estatus', '<>', 'Danado');
            }
        }

        $pendientes = $baseQuery
            ->orderBy('fecha_registro', 'desc')
            ->paginate($perPage)
            ->appends($request->query());

        // Clasificar cada item (añadir razon/prioridad a cada modelo)
        $pendientes->getCollection()->transform(function (Bien $bien) {
            $this->clasificarPendiente($bien);

            return $bien;
        });

        return view('admin.pendientes', [
            'pendientes' => $pendientes,
            'search' => $search,
            'prioridad' => $prioridad,
            'totalPendientes' => $totalPendientes,
            'prioridadAltaCount' => $prioridadAltaCount,
            'sinAsignarCount' => $sinAsignarCount,
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
        ]);
    }

    public function resolver(Request $request, Bien $bien)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'accion' => ['required', 'in:' . implode(',', array_keys(self::ESTATUS_POR_ACCION))],
            'notas' => ['nullable', 'string', 'max:500'],
            'nuevo_estatus' => ['required', 'in:' . implode(',', array_values(self::ESTATUS_POR_ACCION))],
            'id_personal_nuevo' => ['nullable', 'integer', 'exists:personal,id_personal'],
            'id_area_nueva' => ['nullable', 'integer', 'exists:areas,id_area'],
        ]);

        // El estado debe corresponder a la accion elegida.
        $estatusEsperado = self::ESTATUS_POR_ACCION[$data['accion']];
        if ($data['nuevo_estatus'] !== $estatusEsperado) {
            throw ValidationException::withMessages([
                'nuevo_estatus' => 'La accion "' . $data['accion'] . '" solo admite el estado "' . $estatusEsperado . '".',
            ]);
        }

        $esAsignacion = $data['accion'] === 'Asignar';
        $personalAnterior = $bien->id_personal;
        $areaAnterior = $bien->id_area;

        if ($esAsignacion) {
            $idPersonalNuevo = $data['id_personal_nuevo'] ?? $personalAnterior;
            $idAreaNueva = $data['id_area_nueva'] ?? $areaAnterior;

            if (! $idPersonalNuevo && ! $idAreaNueva) {
                throw ValidationException::withMessages([
                    'id_personal_nuevo' => 'Selecciona un responsable o un area para asignar el bien.',
                ]);
            }
        } else {
            $idPersonalNuevo = $personalAnterior;
            $idAreaNueva = $areaAnterior;
        }

        $estatus = $estatusEsperado;

        $bien->update([
            'id_personal' => $idPersonalNuevo,
            'id_area' => $idAreaNueva,
            'estatus' => $estatus,
        ]);

        HistorialAsignacion::create([
            'id_bien' => $bien->id_bien,
            'id_personal_anterior' => $personalAnterior,
            'id_personal_nuevo' => $idPersonalNuevo,
            'id_area_anterior' => $areaAnterior,
            'id_area_nueva' => $idAreaNueva,
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
