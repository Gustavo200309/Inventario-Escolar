<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\HistorialAsignacion;
use App\Models\ParametroSistema;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

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
        $perPage = $request->query('per_page', 25);
        $allowedPerPage = [10, 20, 25, 50];
        if (!in_array((int)$perPage, $allowedPerPage)) {
            $perPage = 25;
        }

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
            ->paginate((int)$perPage)
            ->appends($request->query());

        return view('admin.bienes', [
            'bienes' => $bienes,
            'search' => $search,
            'estatus' => $status,
            'perPage' => (int)$perPage,
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
            'codigo_barras' => ['nullable', 'string', 'max:200'],
            'id_area' => ['nullable', 'integer', 'exists:areas,id_area'],
            'id_personal' => ['nullable', 'integer', 'exists:personal,id_personal'],
            'estatus' => ['required', 'in:Disponible,Asignado,Pendiente,Baja'],
        ]);

        $data['no_inventario'] = $this->generarNoInventario();

        if (empty($data['codigo_barras'])) {
            $data['codigo_barras'] = $data['no_inventario'];
        }

        $bien = Bien::create(array_merge($data, [
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
            'no_inventario' => ['required', 'string', 'max:100'],
            'nombre_bien' => ['required', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'serie' => ['nullable', 'string', 'max:150'],
            'adq' => ['nullable', 'string', 'max:100'],
            'valor' => ['nullable', 'numeric'],
            'resguardo_excel' => ['nullable', 'string', 'max:255'],
            'codigo_barras' => ['nullable', 'string', 'max:200'],
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

    public function downloadBarcodes(Request $request)
    {
        $ids = $request->query('ids');
        if (!$ids) {
            return redirect()->route('admin.bienes')->with('error', 'No se seleccionaron bienes.');
        }

        $idArray = explode(',', $ids);
        $bienes = Bien::whereIn('id_bien', $idArray)
            ->whereNotNull('codigo_barras')
            ->get();

        if ($bienes->isEmpty()) {
            return redirect()->route('admin.bienes')->with('error', 'Los bienes seleccionados no tienen codigo de barras.');
        }

        return view('admin.bienes-barcodes', [
            'bienes' => $bienes,
        ]);
    }

    public function downloadTemplate()
    {
        $headers = [
            'id_sep', 'nombre_bien', 'marca', 'modelo', 'serie',
            'codigo_barras', 'id_area', 'id_personal', 'estatus'
        ];

        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', $headers) . "\n";
        $csv .= '"","Ejemplo: Computadora","Ejemplo: Dell","Ejemplo: OptiPlex 3080","Ejemplo: SN-001","","","","Disponible"' . "\n";

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-importacion.csv"',
        ]);
    }

    public function importExcel(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $archivo = $request->file('archivo');
        $extension = $archivo->getClientOriginalExtension();

        $importados = 0;
        $errores = [];

        try {
            if (in_array($extension, ['csv', 'txt'])) {
                $handle = fopen($archivo->getPathname(), 'r');
                $headers = fgetcsv($handle);

                if (!$headers) {
                    throw new \Exception('El archivo CSV no tiene encabezados.');
                }

                $headers = array_map('trim', $headers);
                $linea = 1;

                while (($row = fgetcsv($handle)) !== false) {
                    $linea++;
                    try {
                        $data = array_combine($headers, $row);
                        $this->importBienFromArray($data);
                        $importados++;
                    } catch (\Exception $e) {
                        $errores[] = "Linea {$linea}: " . $e->getMessage();
                    }
                }
                fclose($handle);
            } else {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                if (count($rows) < 2) {
                    throw new \Exception('El archivo no tiene datos.');
                }

                $headers = array_map('trim', $rows[0]);

                for ($i = 1; $i < count($rows); $i++) {
                    try {
                        $rowData = array_combine($headers, $rows[$i]);
                        if (empty(array_filter($rowData))) continue;
                        $this->importBienFromArray($rowData);
                        $importados++;
                    } catch (\Exception $e) {
                        $errores[] = "Fila " . ($i + 1) . ": " . $e->getMessage();
                    }
                }
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.bienes')->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }

        $mensaje = "Se importaron {$importados} bienes correctamente.";
        if (!empty($errores)) {
            $mensaje .= " Con " . count($errores) . " errores.";
            if (count($errores) <= 5) {
                $mensaje .= " Detalles: " . implode('; ', $errores);
            }
        }

        return redirect()->route('admin.bienes')->with('success', $mensaje);
    }

    private function importBienFromArray(array $data): void
    {
        $areaNombre = trim($data['id_area'] ?? '');
        $personalNombre = trim($data['id_personal'] ?? '');
        $estatus = trim($data['estatus'] ?? 'Disponible');

        $idArea = null;
        if (!empty($areaNombre)) {
            $area = Area::where('nombre_area', $areaNombre)->first();
            if ($area) {
                $idArea = $area->id_area;
            } else {
                $area = Area::create([
                    'nombre_area' => $areaNombre,
                    'descripcion' => 'Importada desde Excel',
                    'estatus' => 'Activa',
                    'fecha_registro' => now(),
                ]);
                $idArea = $area->id_area;
            }
        }

        $idPersonal = null;
        if (!empty($personalNombre)) {
            $personal = Personal::where('nombre', $personalNombre)->first();
            if ($personal) {
                $idPersonal = $personal->id_personal;
            } else {
                $personal = Personal::create([
                    'nombre' => $personalNombre,
                    'apellido_paterno' => '',
                    'puesto' => 'Importado',
                    'id_area' => $idArea,
                    'estatus' => 'Activo',
                    'fecha_registro' => now(),
                ]);
                $idPersonal = $personal->id_personal;
            }
        }

        $noInventario = $this->generarNoInventario();

        $codigoBarras = trim($data['codigo_barras'] ?? '');
        if (empty($codigoBarras)) {
            $codigoBarras = $noInventario;
        }

        $bienData = [
            'id_sep' => trim($data['id_sep'] ?? ''),
            'no_inventario' => $noInventario,
            'nombre_bien' => trim($data['nombre_bien'] ?? ''),
            'marca' => trim($data['marca'] ?? ''),
            'modelo' => trim($data['modelo'] ?? ''),
            'serie' => trim($data['serie'] ?? ''),
            'codigo_barras' => $codigoBarras,
            'id_area' => $idArea,
            'id_personal' => $idPersonal,
            'estatus' => in_array($estatus, ['Disponible', 'Asignado', 'Pendiente', 'Baja']) ? $estatus : 'Disponible',
            'fecha_registro' => now(),
        ];

        if (empty($bienData['nombre_bien'])) {
            throw new \Exception('El nombre del bien es requerido.');
        }

        $bien = Bien::create($bienData);

        if ($bien->id_personal || $bien->id_area) {
            HistorialAsignacion::create([
                'id_bien' => $bien->id_bien,
                'id_personal_anterior' => null,
                'id_personal_nuevo' => $bien->id_personal,
                'id_area_anterior' => null,
                'id_area_nueva' => $bien->id_area,
                'fecha_movimiento' => now(),
                'tipo_movimiento' => 'Asignacion',
                'observaciones' => 'Importado via Excel.',
            ]);
        }
    }

    private function generarNoInventario(): string
    {
        $prefijo = ParametroSistema::where('clave', 'inventario_prefijo')->value('valor') ?? 'INV-';

        $ultimo = Bien::where('no_inventario', 'like', $prefijo . '%')
            ->orderByRaw('CAST(SUBSTRING(no_inventario, LENGTH(?) + 1) AS UNSIGNED) DESC', [$prefijo])
            ->value('no_inventario');

        if ($ultimo) {
            $numero = intval(substr($ultimo, strlen($prefijo))) + 1;
        } else {
            $numero = 1;
        }

        return $prefijo . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }
}
