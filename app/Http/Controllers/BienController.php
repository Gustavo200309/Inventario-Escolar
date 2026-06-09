<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\HistorialAsignacion;
use App\Models\ParametroSistema;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BienController extends Controller
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
        $status = $request->query('estatus');

        $bienes = Bien::with([
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
                    ->orWhere('marca', 'like', "%{$search}%")
            ))
            ->when($status && $status !== 'Todos', fn($query) => $query->where('estatus', $status))
            ->orderBy('fecha_registro', 'desc')
            ->get();

        return view('admin.bienes', [
            'bienes' => $bienes,
            'search' => $search,
            'estatus' => $status,
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.bienes-create', [
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'id_sep' => ['nullable', 'string', 'max:50'],
            'nombre_bien' => ['required', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'serie' => ['nullable', 'string', 'max:150'],
            'adq' => ['nullable', 'string', 'max:100'],
            'valor' => ['nullable', 'numeric'],
            'resguardo_excel' => ['nullable', 'string', 'max:255'],
            'id_area' => ['nullable', 'integer', 'exists:areas,id_area'],
            'id_personal' => ['nullable', 'integer', 'exists:personal,id_personal'],
            'estatus' => ['required', 'in:Disponible,Asignado,Pendiente,Baja'],
        ]);

        $noInventario = $this->generateNoInventario();

        $bien = Bien::create(array_merge($data, [
            'no_inventario' => $noInventario,
            'codigo_barras' => $noInventario,
            'fecha_registro' => now(),
        ]));

        if ($bien->id_personal || $bien->id_area) {
            HistorialAsignacion::create([
                'id_bien' => $bien->id_bien,
                'id_personal_anterior' => null,
                'id_personal_nuevo' => $bien->id_personal,
                'id_area_anterior' => null,
                'id_area_nueva' => $bien->id_area,
                'fecha_movimiento' => now(),
                'tipo_movimiento' => 'Asignacion',
                'observaciones' => 'Registro inicial del bien.',
            ]);
        }

        return redirect()->route('admin.bienes')->with('success', 'Bien registrado correctamente.');
    }

    public function show(Bien $bien): View
    {
        return view('admin.bienes-show', [
            'bien' => $bien->load(['area', 'personal', 'historiales.personalAnterior', 'historiales.personalNuevo', 'historiales.areaAnterior', 'historiales.areaNueva']),
        ]);
    }

    public function edit(Bien $bien): View
    {
        $this->authorizeAdmin();

        return view('admin.bienes-edit', [
            'bien' => $bien,
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Bien $bien)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'id_sep' => ['nullable', 'string', 'max:50'],
            'nombre_bien' => ['required', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'serie' => ['nullable', 'string', 'max:150'],
            'adq' => ['nullable', 'string', 'max:100'],
            'valor' => ['nullable', 'numeric'],
            'resguardo_excel' => ['nullable', 'string', 'max:255'],
            'id_area' => ['nullable', 'integer', 'exists:areas,id_area'],
            'id_personal' => ['nullable', 'integer', 'exists:personal,id_personal'],
            'estatus' => ['required', 'in:Disponible,Asignado,Pendiente,Baja'],
        ]);

        $bien->update($data);

        return redirect()->route('admin.bienes')->with('success', 'Bien actualizado correctamente.');
    }

    public function destroy(Bien $bien)
    {
        $this->authorizeAdmin();

        $bien->delete();

        return redirect()->route('admin.bienes')->with('success', 'Bien eliminado correctamente.');
    }

    public function barcode($code)
    {
        $generator = new BarcodeGeneratorSVG;
        $svg = $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 50);

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    private function generateNoInventario(): string
    {
        $prefix = ParametroSistema::where('clave', 'inventario_prefijo')->value('valor') ?? 'INV-';
        $lastBien = Bien::where('no_inventario', 'like', $prefix . '%')
            ->orderBy('id_bien', 'desc')
            ->first();

        if ($lastBien) {
            $lastNum = (int) substr($lastBien->no_inventario, strlen($prefix));
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
