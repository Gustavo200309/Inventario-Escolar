<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EscanerController extends Controller
{
    public function index(): View
    {
        return view('admin.escanear');
    }

    public function buscar(string $codigo): View
    {
        $bien = Bien::with(['area', 'personal', 'marcaRelacion'])
            ->where('codigo_barras', $codigo)
            ->orWhere('no_inventario', $codigo)
            ->first();

        return view('admin.escanear-resultado', [
            'bien' => $bien,
            'codigo' => $codigo,
        ]);
    }
}
