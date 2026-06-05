<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendientesController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $pendientes = Bien::with(['area', 'personal'])
            ->whereIn('estatus', ['Pendiente', 'En revisión', 'Dañado'])
            ->when($search, fn($query) => $query->where('nombre_bien', 'like', "%{$search}%")
                ->orWhere('no_inventario', 'like', "%{$search}%")
                ->orWhere('serie', 'like', "%{$search}%")
            )
            ->orderBy('fecha_registro', 'desc')
            ->get();

        return view('admin.pendientes', [
            'pendientes' => $pendientes,
            'search' => $search,
        ]);
    }
}
