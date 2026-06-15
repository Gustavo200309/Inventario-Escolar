<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\HistorialAsignacion;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $bienesPorMes = collect(range(5, 0))->map(function (int $monthsAgo) {
            $fecha = Carbon::now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => ucfirst($fecha->translatedFormat('M')),
                'total' => Bien::whereYear('fecha_registro', $fecha->year)
                    ->whereMonth('fecha_registro', $fecha->month)
                    ->count(),
            ];
        });

        return view('admin.dashboard', [
            'activeMenu' => 'dashboard',
            'pageTitle' => 'Dashboard administrativo',
            'pageSubtitle' => 'Vista general del sistema para administracion visual.',
            'totalBienes' => Bien::count(),
            'bienesAsignados' => Bien::where('estatus', 'Asignado')->count(),
            'bienesDisponibles' => Bien::where('estatus', 'Disponible')->count(),
            'bienesPendientes' => Bien::whereIn('estatus', ['Pendiente', 'En revision', 'En mantenimiento', 'Danado'])->count(),
            'bienesBaja' => Bien::where('estatus', 'Baja')->count(),
            'personalActivo' => Personal::where('estatus', 'Activo')->count(),
            'areasRegistradas' => Area::where('estatus', 'Activa')->count(),
            'movimientosRecientes' => HistorialAsignacion::with(['bien', 'personalAnterior', 'personalNuevo', 'areaAnterior', 'areaNueva'])
                ->orderBy('fecha_movimiento', 'desc')
                ->limit(6)
                ->get(),
            'bienesPorMes' => $bienesPorMes,
            'maxBienesMes' => max($bienesPorMes->max('total'), 1),
            'user' => Auth::user(),
        ]);
    }
}
