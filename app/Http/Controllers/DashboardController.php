<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\Personal;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'activeMenu' => 'dashboard',
            'pageTitle' => 'Dashboard administrativo',
            'pageSubtitle' => 'Vista general del sistema para administración visual.',
            'totalBienes' => Bien::count(),
            'bienesAsignados' => Bien::where('estatus', 'Asignado')->count(),
            'personalActivo' => Personal::where('estatus', 'Activo')->count(),
            'areasRegistradas' => Area::where('estatus', 'Activa')->count(),
            'user' => Auth::user(),
        ]);
    }
}
