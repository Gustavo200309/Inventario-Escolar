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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BienController extends Controller
{
    private static ?array $cacheClavesUnicas = null;

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

    private const CAMPOS_UNICOS = ['id_sep', 'no_inventario', 'codigo_barras'];

    private function cacheClavesUnicas(): array
    {
        if (self::$cacheClavesUnicas === null) {
            self::$cacheClavesUnicas = array_fill_keys(self::CAMPOS_UNICOS, []);

            Bien::withEliminados()->select(array_merge(['id_bien'], self::CAMPOS_UNICOS))->chunkById(500, function ($bienes) {
                foreach ($bienes as $bien) {
                    foreach (self::CAMPOS_UNICOS as $campo) {
                        $this->registrarClaveUnica($campo, $bien->{$campo}, $bien->id_bien);
                    }
                }
            });
        }

        return self::$cacheClavesUnicas;
    }

    private function registrarClaveUnica(string $campo, ?string $valor, ?int $idBien = null): void
    {
        if ($valor === null || trim($valor) === '') {
            return;
        }

        $clave = $this->normalizarTexto($valor);
        if ($clave === '') {
            return;
        }

        if (self::$cacheClavesUnicas === null) {
            self::$cacheClavesUnicas = array_fill_keys(self::CAMPOS_UNICOS, []);
        }

        self::$cacheClavesUnicas[$campo][$clave] = $idBien ?? true;
    }

    private function removerClaveUnica(string $campo, ?string $valor): void
    {
        if ($valor === null || trim($valor) === '') {
            return;
        }

        $clave = $this->normalizarTexto($valor);
        if ($clave === '') {
            return;
        }

        unset($this->cacheClavesUnicas()[$campo][$clave]);
    }

    private function esClaveDuplicada(string $campo, ?string $valor): bool
    {
        if ($valor === null || trim($valor) === '') {
            return false;
        }

        $clave = $this->normalizarTexto($valor);
        if ($clave === '') {
            return false;
        }

        return isset($this->cacheClavesUnicas()[$campo][$clave]);
    }

    /**
     * Busca el bien existente que coincide con las claves unicas presentes en
     * la fila. Si varias claves apuntan a bienes distintos, lanza un error de
     * ambiguedad para no actualizar el registro equivocado.
     */
    private function encontrarBienExistente(array $data): ?Bien
    {
        $cache = $this->cacheClavesUnicas();
        $candidatos = [];

        foreach (self::CAMPOS_UNICOS as $campo) {
            $valor = trim($data[$campo] ?? '');
            if ($valor === '') {
                continue;
            }

            $clave = $this->normalizarTexto($valor);
            if ($clave === '' || !isset($cache[$campo][$clave])) {
                continue;
            }

            $idBien = $cache[$campo][$clave];
            if (is_int($idBien)) {
                $candidatos[] = $idBien;
            }
        }

        $candidatos = array_values(array_unique($candidatos));

        if (count($candidatos) > 1) {
            throw new \Exception('La fila coincide con mas de un bien existente; no se puede actualizar.');
        }

        if (count($candidatos) === 1) {
            $bien = Bien::withEliminados()->find($candidatos[0]);
            if (!$bien || $bien->eliminado) {
                throw new \Exception('El bien ya esta registrado en el sistema o se repite en el archivo.');
            }

            return $bien;
        }

        return null;
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

    public function detallePublico(string $codigo): View
    {
        $bien = Bien::with(['area', 'personal', 'marcaRelacion', 'historiales.personalAnterior', 'historiales.personalNuevo', 'historiales.areaAnterior', 'historiales.areaNueva'])
            ->where(function ($query) use ($codigo) {
                $query->where('codigo_barras', $codigo)
                    ->orWhere('no_inventario', $codigo);
            })
            ->first();

        return view('public.bien-detalle', [
            'bien' => $bien,
            'codigo' => $codigo,
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
            'id_sep' => ['nullable', 'string', 'min:6', 'max:30', 'regex:/^[a-zA-Z0-9\-\.\/]*$/', Rule::unique('bienes', 'id_sep')],
            'nombre_bien' => ['required', 'string', 'min:3', 'max:255'],
            'marca' => ['nullable', 'string', 'max:100'],
            'id_marca' => ['nullable', 'integer', 'exists:marcas,id_marca'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'serie' => ['nullable', 'string', 'max:150'],
            'adq' => ['nullable', 'string', 'max:100'],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'resguardo_excel' => ['nullable', 'string', 'max:255'],
            'codigo_barras' => ['nullable', 'string', 'max:200', Rule::unique('bienes', 'codigo_barras')],
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
            'bien' => $bien->load([
                'area',
                'personal',
                'marcaRelacion',
                'historiales.personalAnterior',
                'historiales.personalNuevo',
                'historiales.areaAnterior',
                'historiales.areaNueva',
            ]),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
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
            'id_sep' => ['nullable', 'string', 'min:6', 'max:30', 'regex:/^[a-zA-Z0-9\-\.\/]*$/', Rule::unique('bienes', 'id_sep')->ignore($bien->id_bien, 'id_bien')],
            'no_inventario' => ['required', 'string', 'min:3', 'max:100', Rule::unique('bienes', 'no_inventario')->ignore($bien->id_bien, 'id_bien')],
            'nombre_bien' => ['required', 'string', 'min:3', 'max:255'],
            'marca' => ['nullable', 'string', 'max:100'],
            'id_marca' => ['nullable', 'integer', 'exists:marcas,id_marca'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'serie' => ['nullable', 'string', 'max:150'],
            'adq' => ['nullable', 'string', 'max:100'],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'resguardo_excel' => ['nullable', 'string', 'max:255'],
            'codigo_barras' => ['nullable', 'string', 'max:200', Rule::unique('bienes', 'codigo_barras')->ignore($bien->id_bien, 'id_bien')],
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

    public function restaurar(int $bien)
    {
        $this->authorizeAdmin();

        Bien::withEliminados()->findOrFail($bien)->update(['eliminado' => false]);

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

    public function forceDestroy(int $bien)
    {
        $this->authorizeAdmin();

        $bien = Bien::withEliminados()->findOrFail($bien);
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
        $all = $request->query('all');

        if ($all) {
            $bienes = Bien::where('eliminado', 0)
                ->whereNotNull('codigo_barras')
                ->get();
        } elseif ($ids) {
            $idArray = explode(',', $ids);
            $bienes = Bien::whereIn('id_bien', $idArray)
                ->whereNotNull('codigo_barras')
                ->get();
        } else {
            return redirect()->route('admin.bienes')->with('error', 'No se seleccionaron bienes.');
        }

        if ($bienes->isEmpty()) {
            return redirect()->route('admin.bienes')->with('error', 'Los bienes seleccionados no tienen codigo de barras.');
        }

        return view('admin.bienes-barcodes', [
            'bienes' => $bienes,
        ]);
    }

    public function barcodesJson(Request $request)
    {
        $ids = $request->query('ids');
        $all = $request->query('all');

        if ($all) {
            $bienes = Bien::where('eliminado', 0)
                ->whereNotNull('codigo_barras')
                ->select('codigo_barras', 'nombre_bien', 'id_sep', 'no_inventario')
                ->get();
        } elseif ($ids) {
            $idArray = explode(',', $ids);
            $bienes = Bien::whereIn('id_bien', $idArray)
                ->whereNotNull('codigo_barras')
                ->select('codigo_barras', 'nombre_bien', 'id_sep', 'no_inventario')
                ->get();
        } else {
            return response()->json([]);
        }

        return response()->json($bienes);
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
        $actualizados = 0;
        $omitidos = 0;
        $errores = [];
        self::$cacheClavesUnicas = null;
        $this->cacheClavesUnicas();

        try {
            if (in_array($extension, ['csv', 'txt'])) {
                $handle = fopen($archivo->getPathname(), 'r');
                $primeraLinea = fgets($handle);

                if ($primeraLinea === false) {
                    throw new \Exception('El archivo CSV no tiene encabezados.');
                }

                $delimitador = $this->detectarDelimitador($primeraLinea);
                rewind($handle);
                $headers = fgetcsv($handle, 0, $delimitador);

                if (!$headers) {
                    throw new \Exception('El archivo CSV no tiene encabezados.');
                }

                $headers = array_map([$this, 'normalizarEncabezado'], array_map('trim', array_map([$this, 'aUtf8'], $headers)));
                $headers = array_values(array_filter($headers, fn($h) => $h !== ''));
                $linea = 1;

                while (($row = fgetcsv($handle, 0, $delimitador)) !== false) {
                    $linea++;
                    try {
                        $row = array_map([$this, 'aUtf8'], $row);
                        $row = array_slice($row, 0, count($headers));
                        if (empty(array_filter($row, fn($v) => trim((string) $v) !== ''))) continue;
                        $data = array_combine($headers, $row);
                        $resultado = $this->importBienFromArray($data);
                        if ($resultado === 'actualizado') {
                            $actualizados++;
                        } else {
                            $importados++;
                        }
                    } catch (\Illuminate\Database\QueryException $e) {
                        $errores[] = "Linea {$linea}: No se pudo guardar el registro, revisa el formato de los datos.";
                    } catch (\Exception $e) {
                        if ($this->esErrorDuplicado($e)) {
                            $omitidos++;
                        } else {
                            $errores[] = "Linea {$linea}: " . $e->getMessage();
                        }
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

                $headers = array_map([$this, 'normalizarEncabezado'], array_map('trim', array_map([$this, 'aUtf8'], $rows[0])));
                $headers = array_values(array_filter($headers, fn($h) => $h !== ''));

                for ($i = 1; $i < count($rows); $i++) {
                    try {
                        $row = array_map([$this, 'aUtf8'], $rows[$i]);
                        $row = array_slice($row, 0, count($headers));
                        if (empty(array_filter($row, fn($v) => trim((string) $v) !== ''))) continue;
                        $rowData = array_combine($headers, $row);
                        $resultado = $this->importBienFromArray($rowData);
                        if ($resultado === 'actualizado') {
                            $actualizados++;
                        } else {
                            $importados++;
                        }
                    } catch (\Illuminate\Database\QueryException $e) {
                        $errores[] = "Fila " . ($i + 1) . ": No se pudo guardar el registro, revisa el formato de los datos.";
                    } catch (\Exception $e) {
                        if ($this->esErrorDuplicado($e)) {
                            $omitidos++;
                        } else {
                            $errores[] = "Fila " . ($i + 1) . ": " . $e->getMessage();
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.bienes')->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }

        $mensaje = "Se importaron {$importados} bienes correctamente.";
        if ($actualizados > 0) {
            $mensaje .= " Se actualizaron {$actualizados} registros existentes.";
        }
        if ($omitidos > 0) {
            $mensaje .= " Se omitieron {$omitidos} registros ya existentes en el sistema.";
        }
        if (!empty($errores)) {
            $mensaje .= " Se encontraron " . count($errores) . " errores.";
            if (count($errores) <= 5) {
                $mensaje .= " Detalles: " . implode('; ', $errores);
            }
        }

        return redirect()->route('admin.bienes')->with('success', $mensaje);
    }

    private function esErrorDuplicado(\Throwable $e): bool
    {
        return mb_strpos($e->getMessage(), 'ya esta registrado en el sistema o se repite en el archivo') !== false;
    }

    /**
     * Detecta el separador real del CSV. Excel en configuracion regional en
     * espanol guarda los archivos con punto y coma en lugar de coma.
     */
    private function detectarDelimitador(string $primeraLinea): string
    {
        $primeraLinea = preg_replace('/^\xEF\xBB\xBF/', '', $primeraLinea) ?? $primeraLinea;

        $conteos = [
            ',' => substr_count($primeraLinea, ','),
            ';' => substr_count($primeraLinea, ';'),
            "\t" => substr_count($primeraLinea, "\t"),
        ];
        arsort($conteos);
        $delimitador = array_key_first($conteos);

        return $conteos[$delimitador] > 0 ? $delimitador : ',';
    }

    /**
     * Normaliza a UTF-8 los valores leidos del archivo. Excel guarda los CSV
     * en Windows-1252, lo que corrompe los acentos y rompe la insercion.
     */
    private function aUtf8($valor)
    {
        if (!is_string($valor)) {
            return $valor;
        }

        $valor = preg_replace('/^\xEF\xBB\xBF/', '', $valor) ?? $valor;

        if ($valor === '' || mb_check_encoding($valor, 'UTF-8')) {
            return $valor;
        }

        return mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');
    }

    private function normalizarEncabezado(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
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

    private function importBienFromArray(array $data): string
    {
        $areaValor = trim($data['id_area'] ?? '');
        $personalValor = trim($data['id_personal'] ?? '');

        $idArea = null;
        if (!empty($areaValor)) {
            if (ctype_digit($areaValor)) {
                $area = Area::find((int) $areaValor);
                if ($area) {
                    $idArea = $area->id_area;
                }
            } else {
                $area = Area::where('nombre_area', $areaValor)->first();
                if ($area) {
                    $idArea = $area->id_area;
                } else {
                    $area = Area::create([
                        'nombre_area' => $areaValor,
                        'descripcion' => 'Importada desde Excel',
                        'estatus' => 'Activa',
                        'fecha_registro' => now(),
                    ]);
                    $idArea = $area->id_area;
                }
            }
        }

        $idPersonal = null;
        if (!empty($personalValor)) {
            if (ctype_digit($personalValor)) {
                $personal = Personal::find((int) $personalValor);
                if ($personal) {
                    $idPersonal = $personal->id_personal;
                }
            } else {
                $personal = Personal::where('nombre', $personalValor)->first();
                if ($personal) {
                    $idPersonal = $personal->id_personal;
                } else {
                    $personal = Personal::create([
                        'nombre' => $personalValor,
                        'apellido_paterno' => '',
                        'puesto' => 'Importado',
                        'id_area' => $idArea,
                        'estatus' => 'Activo',
                        'fecha_registro' => now(),
                    ]);
                    $idPersonal = $personal->id_personal;
                }
            }
        }

        $idSep = trim($data['id_sep'] ?? '');
        if ($idSep !== '' && mb_strlen($idSep) < 6) {
            throw new \Exception('El ID SEP "' . $idSep . '" debe tener al menos 6 caracteres.');
        }

        $marcaNombre = trim($data['marca'] ?? '');
        $idMarca = null;
        if (!empty($marcaNombre)) {
            $marca = Marca::firstOrCreate(['nombre_marca' => $marcaNombre]);
            $idMarca = $marca->id_marca;
        }

        $valorRaw = trim($data['valor'] ?? '');
        $valor = null;
        if ($valorRaw !== '') {
            $valor = floatval(str_replace([',', '$', ' '], '', $valorRaw));
        }

        $existente = $this->encontrarBienExistente($data);
        if ($existente) {
            return $this->actualizarBien($existente, $data, $idArea, $idPersonal, $valor, $idMarca, $marcaNombre);
        }

        $noInventario = trim($data['no_inventario'] ?? '');
        if ($noInventario !== '' && mb_strlen($noInventario) < 3) {
            throw new \Exception('El numero de inventario "' . $noInventario . '" debe tener al menos 3 caracteres.');
        }
        if (empty($noInventario)) {
            $noInventario = $this->generarNoInventario();
        }

        $codigoBarras = trim($data['codigo_barras'] ?? '');
        if (empty($codigoBarras)) {
            $codigoBarras = $this->generarCodigoBarras();
        }

        $estatus = trim($data['estatus'] ?? 'Disponible');

        $bienData = [
            'id_sep' => $idSep,
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

        if ($this->esClaveDuplicada('id_sep', $bienData['id_sep'])
            || $this->esClaveDuplicada('no_inventario', $bienData['no_inventario'])
            || $this->esClaveDuplicada('codigo_barras', $bienData['codigo_barras'])) {
            throw new \Exception('El bien ya esta registrado en el sistema o se repite en el archivo.');
        }

        $bien = Bien::create($bienData);

        foreach (self::CAMPOS_UNICOS as $campo) {
            $this->registrarClaveUnica($campo, $bienData[$campo] ?? null, $bien->id_bien);
        }

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

        return 'creado';
    }

    private function actualizarBien(Bien $existente, array $data, ?int $idArea, ?int $idPersonal, ?float $valor, ?int $idMarca, string $marcaNombre): string
    {
        $areaValor = trim($data['id_area'] ?? '');
        $personalValor = trim($data['id_personal'] ?? '');

        $nombre = trim($data['nombre_bien'] ?? '');
        if ($nombre === '') {
            throw new \Exception('El nombre del bien es requerido.');
        }

        $nuevoIdSep = trim($data['id_sep'] ?? '');
        if ($nuevoIdSep !== '' && mb_strlen($nuevoIdSep) < 6) {
            throw new \Exception('El ID SEP "' . $nuevoIdSep . '" debe tener al menos 6 caracteres.');
        }

        $nuevoNoInventario = trim($data['no_inventario'] ?? '');
        if ($nuevoNoInventario !== '' && mb_strlen($nuevoNoInventario) < 3) {
            throw new \Exception('El numero de inventario "' . $nuevoNoInventario . '" debe tener al menos 3 caracteres.');
        }

        $cambios = [
            'nombre_bien' => $nombre,
            'modelo' => trim($data['modelo'] ?? ''),
            'serie' => trim($data['serie'] ?? ''),
            'adq' => trim($data['adq'] ?? ''),
        ];

        if ($marcaNombre !== '') {
            $cambios['marca'] = $marcaNombre;
            $cambios['id_marca'] = $idMarca;
        }

        if ($nuevoIdSep !== '') {
            $cambios['id_sep'] = $nuevoIdSep;
        }

        if ($nuevoNoInventario !== '') {
            $cambios['no_inventario'] = $nuevoNoInventario;
        }

        $nuevoCodigoBarras = trim($data['codigo_barras'] ?? '');
        if ($nuevoCodigoBarras !== '') {
            $cambios['codigo_barras'] = $nuevoCodigoBarras;
        }

        if (!empty($areaValor) && $idArea !== null) {
            $cambios['id_area'] = $idArea;
        }

        if (!empty($personalValor) && $idPersonal !== null) {
            $cambios['id_personal'] = $idPersonal;
        }

        $estatusValor = trim($data['estatus'] ?? '');
        if ($estatusValor !== '') {
            $cambios['estatus'] = in_array($estatusValor, ['Disponible', 'Asignado', 'Pendiente', 'Baja']) ? $estatusValor : 'Disponible';
        }

        if ($valor !== null) {
            $cambios['valor'] = $valor;
        }

        foreach (self::CAMPOS_UNICOS as $campo) {
            if (!array_key_exists($campo, $cambios)) {
                continue;
            }

            $clave = $this->normalizarTexto((string) $cambios[$campo]);
            $cache = $this->cacheClavesUnicas();
            if (isset($cache[$campo][$clave]) && $cache[$campo][$clave] !== $existente->id_bien) {
                throw new \Exception('El ' . $campo . ' "' . $cambios[$campo] . '" ya esta registrado en el sistema o se repite en el archivo.');
            }
        }

        $originales = [];
        foreach (self::CAMPOS_UNICOS as $campo) {
            $originales[$campo] = $existente->{$campo};
        }

        $existente->update($cambios);

        foreach (self::CAMPOS_UNICOS as $campo) {
            if (array_key_exists($campo, $cambios)) {
                $this->removerClaveUnica($campo, $originales[$campo]);
                $this->registrarClaveUnica($campo, $cambios[$campo], $existente->id_bien);
            }
        }

        return 'actualizado';
    }

    private function generarNoInventario(): string
    {
        $prefijo = ParametroSistema::where('clave', 'inventario_prefijo')->value('valor') ?? 'INV-';

        $ultimo = Bien::withEliminados()->where('no_inventario', 'like', $prefijo . '%')
            ->orderByRaw('CAST(SUBSTRING(no_inventario, LENGTH(?) + 1) AS UNSIGNED) DESC', [$prefijo])
            ->value('no_inventario');

        if ($ultimo) {
            $numero = intval(substr($ultimo, strlen($prefijo))) + 1;
        } else {
            $numero = 1;
        }

        do {
            $noInventario = $prefijo . str_pad($numero, 5, '0', STR_PAD_LEFT);
            $numero++;
        } while (self::$cacheClavesUnicas !== null && $this->esClaveDuplicada('no_inventario', $noInventario));

        return $noInventario;
    }

    private function generarCodigoBarras(): string
    {
        do {
            $codigo = strtoupper(bin2hex(random_bytes(3)));
        } while (Bien::where('codigo_barras', $codigo)->exists()
            || (self::$cacheClavesUnicas !== null && $this->esClaveDuplicada('codigo_barras', $codigo)));

        return $codigo;
    }
}