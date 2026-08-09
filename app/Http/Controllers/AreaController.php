<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ImportableExcel;
use App\Models\Area;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AreaController extends Controller
{
    use ImportableExcel;

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

        $areas = Area::withCount(['bienes', 'personal'])
            ->when($search, fn ($query) => $query->where('nombre_area', 'like', "%{$search}%"))
            ->orderBy('nombre_area')
            ->get();

        return view('admin.areas', [
            'areas' => $areas,
            'search' => $search,
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.areas-create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'nombre_area' => ['required', 'string', 'min:2', 'max:150', Rule::unique('areas', 'nombre_area')],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estatus' => ['required', 'in:Activa,Inactiva'],
        ]);

        Area::create(array_merge($data, [
            'fecha_registro' => now(),
        ]));

        return redirect()->route('admin.areas')->with('success', 'Área registrada correctamente.');
    }

    public function show(Area $area): View
    {
        return view('admin.areas-show', [
            'area' => $area->load(['bienes', 'personal']),
        ]);
    }

    public function edit(Area $area): View
    {
        $this->authorizeAdmin();

        return view('admin.areas-edit', [
            'area' => $area,
        ]);
    }

    public function update(Request $request, Area $area)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'nombre_area' => ['required', 'string', 'min:2', 'max:150', Rule::unique('areas', 'nombre_area')->ignore($area->id_area, 'id_area')],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estatus' => ['required', 'in:Activa,Inactiva'],
        ]);

        $area->update($data);

        return redirect()->route('admin.areas')->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area)
    {
        $this->authorizeAdmin();

        // La clave foranea deja el bien sin area; si ademas queda sin responsable,
        // no puede seguir marcado como "Asignado".
        $afectados = Bien::withEliminados()->where('id_area', $area->id_area)->pluck('id_bien');

        $area->delete();

        if ($afectados->isNotEmpty()) {
            Bien::withEliminados()
                ->whereIn('id_bien', $afectados)
                ->whereNull('id_area')
                ->whereNull('id_personal')
                ->where('estatus', 'Asignado')
                ->update(['estatus' => 'Disponible']);
        }

        return redirect()->route('admin.areas')->with('success', 'Área eliminada correctamente.');
    }

    public function downloadTemplate()
    {
        $headers = ['nombre_area', 'descripcion', 'estatus'];

        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', $headers)."\n";
        $csv .= '"Dirección","Área institucional","Activa"'."\n";

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-areas.csv"',
        ]);
    }

    public function importExcel(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $mapaEncabezados = [
            'nombre_area' => 'nombre_area',
            'nombre del area' => 'nombre_area',
            'nombre de la area' => 'nombre_area',
            'area' => 'nombre_area',
            'descripcion' => 'descripcion',
            'estatus' => 'estatus',
            'estado' => 'estatus',
        ];

        try {
            [$importados, $actualizados, $errores] = $this->leerArchivoImportacion(
                $request,
                fn (array $data) => $this->importAreaFromArray($data),
                $mapaEncabezados
            );
        } catch (\Exception $e) {
            return redirect()->route('admin.areas')->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }

        $mensaje = "Se importaron {$importados} áreas correctamente.";
        if ($actualizados > 0) {
            $mensaje .= " Se actualizaron {$actualizados} áreas existentes.";
        }
        if (! empty($errores)) {
            $mensaje .= ' Se encontraron '.count($errores).' errores.';
            if (count($errores) <= 5) {
                $mensaje .= ' Detalles: '.implode('; ', $errores);
            }
        }

        return redirect()->route('admin.areas')->with('success', $mensaje);
    }

    private function importAreaFromArray(array $data): string
    {
        $nombre = trim($data['nombre_area'] ?? '');
        if ($nombre === '') {
            throw new \Exception('El nombre del área es requerido.');
        }
        if (mb_strlen($nombre) < 2) {
            throw new \Exception('El nombre del área "'.$nombre.'" debe tener al menos 2 caracteres.');
        }
        if (mb_strlen($nombre) > 150) {
            throw new \Exception('El nombre del área "'.$nombre.'" no puede exceder 150 caracteres.');
        }

        $descripcion = trim($data['descripcion'] ?? '');
        if (mb_strlen($descripcion) > 500) {
            throw new \Exception('La descripción del área "'.$nombre.'" no puede exceder 500 caracteres.');
        }

        $estatus = trim($data['estatus'] ?? '');
        if ($estatus === '') {
            $estatus = 'Activa';
        }
        if (! in_array($estatus, ['Activa', 'Inactiva'])) {
            throw new \Exception('El estado "'.$estatus.'" no es válido para el área "'.$nombre.'". Use Activa o Inactiva.');
        }

        $area = Area::whereRaw('LOWER(nombre_area) = ?', [mb_strtolower($nombre)])->first();

        if ($area) {
            $area->update([
                'descripcion' => $descripcion,
                'estatus' => $estatus,
            ]);

            return 'actualizado';
        }

        Area::create([
            'nombre_area' => $nombre,
            'descripcion' => $descripcion,
            'estatus' => $estatus,
            'fecha_registro' => now(),
        ]);

        return 'creado';
    }
}
