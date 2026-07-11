<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\HistorialAsignacion;
use App\Models\Marca;
use App\Models\ParametroSistema;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class BienController extends Controller
{
    private static ?array $cacheNombresBienes = null;

    private function normalizarTexto(string $texto): string
    {
        $texto = trim($texto);
        $texto = $this->corregirCodificacion($texto);
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto);
        $texto = preg_replace('/[\x80-\x9F]/u', '', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        if (function_exists('normalizer_normalize')) {
            $texto = normalizer_normalize($texto, \Normalizer::FORM_D);
            $texto = preg_replace('/\p{M}/u', '', $texto);
        }
        $texto = mb_strtolower($texto);
        return $texto;
    }

    private function corregirCodificacion(string $texto): string
    {
        $texto = str_replace("\xC2\xA5", "\xC3\x91", $texto);
        $texto = str_replace("\xEF\xBF\xBD", '', $texto);
        $texto = str_replace("\xC3\xAF\xC2\xBF\xC2\xBD", '', $texto);
        $dobleCodificado = preg_match('/\xC3[\x80-\xBF]/', $texto);
        if ($dobleCodificado) {
            $latin1 = mb_convert_encoding($texto, 'latin1', 'UTF-8');
            $corregido = mb_convert_encoding($latin1, 'UTF-8', 'latin1');
            if (mb_check_encoding($corregido, 'UTF-8') && $corregido !== $texto) {
                $texto = $corregido;
            }
        }
        $texto = str_replace('Ã?', 'Ñ', $texto);
        return $texto;
    }

    private function existeBien(string $nombreBien): bool
    {
        if (self::$cacheNombresBienes === null) {
            $nombres = Bien::where('eliminado', false)->pluck('nombre_bien')->toArray();
            self::$cacheNombresBienes = [];
            foreach ($nombres as $nombre) {
                self::$cacheNombresBienes[$this->normalizarTexto($nombre)] = true;
            }
        }
        return isset(self::$cacheNombresBienes[$this->normalizarTexto($nombreBien)]);
    }

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
            'marcaRelacion',
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
            'marcas' => Marca::orderBy('nombre_marca')->get(),
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
            'id_marca' => ['nullable', 'integer', 'exists:marcas,id_marca'],
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
            $data['codigo_barras'] = $this->generarCodigoBarras();
        }

        if (!empty($data['id_marca'])) {
            $data['marca'] = Marca::find($data['id_marca'])?->nombre_marca;
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
            'id_marca' => ['nullable', 'integer', 'exists:marcas,id_marca'],
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

        if (!empty($data['id_marca'])) {
            $data['marca'] = Marca::find($data['id_marca'])?->nombre_marca;
        }

        $bien->update($data);

        return redirect()->route('admin.bienes')->with('success', 'Bien actualizado correctamente.');
    }

    public function destroy(Bien $bien)
    {
        $this->authorizeAdmin();

        $bien->delete();

        return redirect()->route('admin.bienes')->with('success', 'Bien enviado a la papelera correctamente.');
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorizeAdmin();

        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        if (!$ids || !is_array($ids) || empty($ids)) {
            return redirect()->route('admin.bienes')->with('error', 'No se seleccionaron bienes.');
        }

        Bien::whereIn('id_bien', $ids)->update(['eliminado' => true]);

        return redirect()->route('admin.bienes')->with('success', count($ids) . ' bien(es) enviado(s) a la papelera correctamente.');
    }

    public function destroyAll()
    {
        $this->authorizeAdmin();

        $count = Bien::where('eliminado', false)->update(['eliminado' => true]);

        return redirect()->route('admin.bienes')->with('success', $count . ' bien(es) enviado(s) a la papelera correctamente.');
    }

    public function restoreAll()
    {
        $this->authorizeAdmin();

        $count = Bien::withEliminados()->where('eliminado', true)->update(['eliminado' => false]);

        return redirect()->route('admin.bienes.papelera')->with('success', $count . ' bien(es) restaurado(s) correctamente.');
    }

    public function forceDestroyAll()
    {
        $this->authorizeAdmin();

        $trashed = Bien::withEliminados()->where('eliminado', true)->pluck('id_bien');
        HistorialAsignacion::whereIn('id_bien', $trashed)->delete();
        $count = Bien::withEliminados()->where('eliminado', true)->delete();

        return redirect()->route('admin.bienes.papelera')->with('success', $count . ' bien(es) eliminado(s) permanentemente.');
    }

    public function papelera(Request $request): View
    {
        $this->authorizeAdmin();

        $search = $request->query('search');

        $bienes = Bien::withEliminados()
            ->where('eliminado', true)
            ->with(['area', 'personal', 'marcaRelacion'])
            ->when($search, fn($query) => $query->where(fn($query) =>
                $query->where('nombre_bien', 'like', "%{$search}%")
                    ->orWhere('serie', 'like', "%{$search}%")
                    ->orWhere('no_inventario', 'like', "%{$search}%")
                    ->orWhere('codigo_barras', 'like', "%{$search}%")
            ))
            ->orderBy('fecha_registro', 'desc')
            ->paginate(25)
            ->appends($request->query());

        return view('admin.bienes-papelera', [
            'bienes' => $bienes,
            'search' => $search,
            'user' => Auth::user(),
        ]);
    }

    public function restaurar(Bien $bien)
    {
        $this->authorizeAdmin();

        $bien = Bien::withEliminados()->findOrFail($bien->id_bien);
        $bien->update(['eliminado' => false]);

        return redirect()->route('admin.bienes.papelera')->with('success', 'Bien restaurado correctamente.');
    }

    public function bulkRestore(Request $request)
    {
        $this->authorizeAdmin();

        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        if (!$ids || !is_array($ids) || empty($ids)) {
            return redirect()->route('admin.bienes.papelera')->with('error', 'No se seleccionaron bienes.');
        }

        Bien::withEliminados()->whereIn('id_bien', $ids)->update(['eliminado' => false]);

        return redirect()->route('admin.bienes.papelera')->with('success', count($ids) . ' bien(es) restaurado(s) correctamente.');
    }

    public function forceDestroy(Bien $bien)
    {
        $this->authorizeAdmin();

        $bien = Bien::withEliminados()->findOrFail($bien->id_bien);
        $bien->historiales()->delete();
        Bien::withEliminados()->where('id_bien', $bien->id_bien)->delete();

        return redirect()->route('admin.bienes.papelera')->with('success', 'Bien eliminado permanentemente.');
    }

    public function bulkForceDestroy(Request $request)
    {
        $this->authorizeAdmin();

        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        if (!$ids || !is_array($ids) || empty($ids)) {
            return redirect()->route('admin.bienes.papelera')->with('error', 'No se seleccionaron bienes.');
        }

        HistorialAsignacion::whereIn('id_bien', $ids)->delete();
        Bien::withEliminados()->whereIn('id_bien', $ids)->delete();

        return redirect()->route('admin.bienes.papelera')->with('success', count($ids) . ' bien(es) eliminado(s) permanentemente.');
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
        self::$cacheNombresBienes = null;

        try {
            if (in_array($extension, ['csv', 'txt'])) {
                $handle = fopen($archivo->getPathname(), 'r');
                $headers = fgetcsv($handle);

                if (!$headers) {
                    throw new \Exception('El archivo CSV no tiene encabezados.');
                }

                $headers = array_map([$this, 'normalizarEncabezado'], array_map('trim', $headers));
                $headers = array_values(array_filter($headers, fn($h) => $h !== ''));
                $linea = 1;

                while (($row = fgetcsv($handle)) !== false) {
                    $linea++;
                    try {
                        $row = array_slice($row, 0, count($headers));
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

                $headers = array_map([$this, 'normalizarEncabezado'], array_map('trim', $rows[0]));
                $headers = array_values(array_filter($headers, fn($h) => $h !== ''));

                for ($i = 1; $i < count($rows); $i++) {
                    try {
                        $row = array_slice($rows[$i], 0, count($headers));
                        if (empty(array_filter($row))) continue;
                        $rowData = array_combine($headers, $row);
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

    private function normalizarEncabezado(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $map = [
            'id-sep' => 'id_sep',
            'id_sep' => 'id_sep',
            'no. inventario' => 'no_inventario',
            'no_inventario' => 'no_inventario',
            'nombre del bien' => 'nombre_bien',
            'nombre_bien' => 'nombre_bien',
            'marca' => 'marca',
            'modelo' => 'modelo',
            'serie' => 'serie',
            'adq' => 'adq',
            'valor' => 'valor',
            'resguardo actual' => 'id_area',
            'resguardo' => 'id_area',
            'codigo_barras' => 'codigo_barras',
            'codigo de barras' => 'codigo_barras',
            'id_area' => 'id_area',
            'id_personal' => 'id_personal',
            'estatus' => 'estatus',
        ];
        return $map[$header] ?? $header;
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

        $noInventario = trim($data['no_inventario'] ?? '');
        if (empty($noInventario)) {
            $noInventario = $this->generarNoInventario();
        }

        $codigoBarras = trim($data['codigo_barras'] ?? '');
        if (empty($codigoBarras)) {
            $codigoBarras = $this->generarCodigoBarras();
        }

        $valorRaw = trim($data['valor'] ?? '');
        $valor = null;
        if ($valorRaw !== '') {
            $valor = floatval(str_replace([',', '$', ' '], '', $valorRaw));
        }

        $marcaNombre = trim($data['marca'] ?? '');
        $idMarca = null;
        if (!empty($marcaNombre)) {
            $marca = Marca::firstOrCreate(['nombre_marca' => $marcaNombre]);
            $idMarca = $marca->id_marca;
        }

        $bienData = [
            'id_sep' => trim($data['id_sep'] ?? ''),
            'no_inventario' => $noInventario,
            'nombre_bien' => trim($data['nombre_bien'] ?? ''),
            'marca' => $marcaNombre,
            'id_marca' => $idMarca,
            'modelo' => trim($data['modelo'] ?? ''),
            'serie' => trim($data['serie'] ?? ''),
            'adq' => trim($data['adq'] ?? ''),
            'valor' => $valor,
            'codigo_barras' => $codigoBarras,
            'id_area' => $idArea,
            'id_personal' => $idPersonal,
            'estatus' => in_array($estatus, ['Disponible', 'Asignado', 'Pendiente', 'Baja']) ? $estatus : 'Disponible',
            'fecha_registro' => now(),
        ];

        if (empty($bienData['nombre_bien'])) {
            throw new \Exception('El nombre del bien es requerido.');
        }

        if ($this->existeBien($bienData['nombre_bien'])) {
            throw new \Exception('El bien "' . $bienData['nombre_bien'] . '" ya existe en el sistema.');
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

    private function generarCodigoBarras(): string
    {
        do {
            $codigo = strtoupper(bin2hex(random_bytes(3)));
        } while (Bien::where('codigo_barras', $codigo)->exists());

        return $codigo;
    }
}
