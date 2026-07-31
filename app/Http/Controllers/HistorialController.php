<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\HistorialAsignacion;
use App\Models\Personal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class HistorialController extends Controller
{
    private const TIPOS_MOVIMIENTO = 'Asignacion,Transferencia,Devolucion,Reasignacion,Cambio de area,Resolucion';

    private const TIPOS = [
        'Asignacion',
        'Transferencia',
        'Devolucion',
        'Reasignacion',
        'Cambio de area',
        'Resolucion',
    ];

    public function index(Request $request): View
    {
        $perPage = (int) $request->query('per_page', 25);
        $allowedPerPage = [10, 20, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }

        $paginator = $this->queryHistorial($request)
            ->orderBy('fecha_movimiento', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $historiales = $paginator->getCollection()
            ->groupBy('tipo_movimiento')
            ->sortBy(function ($items, $tipo) {
                $index = array_search($tipo, self::TIPOS, true);

                return $index === false ? PHP_INT_MAX : $index;
            });

        return view('admin.historial', [
            'historiales' => $historiales,
            'historialesPaginator' => $paginator,
            'search' => $request->query('search'),
            'tipo' => $request->query('tipo'),
            'fechaInicio' => $request->query('fecha_inicio'),
            'fechaFin' => $request->query('fecha_fin'),
            'tipos' => HistorialAsignacion::query()
                ->select('tipo_movimiento')
                ->distinct()
                ->orderBy('tipo_movimiento')
                ->pluck('tipo_movimiento')
                ->filter(),
            'user' => Auth::user(),
        ]);
    }

    public function export(Request $request, string $format)
    {
        $format = strtolower($format);
        $historiales = $this->queryHistorial($request)
            ->orderBy('fecha_movimiento', 'desc')
            ->get();

        if ($format === 'excel' || $format === 'csv') {
            $csv = "\xEF\xBB\xBF" . $this->csvRow([
                'Tipo',
                'Fecha',
                'Bien',
                'No. inventario',
                'Codigo de barras',
                'Responsable anterior',
                'Responsable nuevo',
                'Area anterior',
                'Area nueva',
                'Observaciones',
            ]);

            foreach ($historiales as $historial) {
                $csv .= $this->csvRow([
                    $historial->tipo_movimiento,
                    $historial->fecha_movimiento?->format('Y-m-d H:i:s'),
                    $historial->bien?->nombre_bien,
                    $historial->bien?->no_inventario,
                    $historial->bien?->codigo_barras,
                    $historial->personalAnterior?->nombre_completo,
                    $historial->personalNuevo?->nombre_completo,
                    $historial->areaAnterior?->nombre_area,
                    $historial->areaNueva?->nombre_area,
                    $historial->observaciones,
                ]);
            }

            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="historial-movimientos.csv"',
            ]);
        }

        if ($format === 'pdf') {
            $lines = ['Historial de movimientos', ''];

            foreach ($historiales as $historial) {
                $lines[] = sprintf(
                    '%s | %s | %s | %s -> %s | %s',
                    $historial->tipo_movimiento,
                    $historial->fecha_movimiento?->format('d/m/Y H:i') ?: 'Sin fecha',
                    $historial->bien?->nombre_bien ?: 'Sin bien',
                    $historial->personalAnterior?->nombre_completo ?: 'Sin responsable',
                    $historial->personalNuevo?->nombre_completo ?: 'Sin responsable',
                    $historial->observaciones ?: 'Sin observaciones'
                );
            }

            return Response::make($this->simplePdf($lines), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="historial-movimientos.pdf"',
            ]);
        }

        return redirect()->route('admin.historial')->with('error', 'Formato de exportacion no valido.');
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.historial-create', [
            'bienes' => Bien::with(['area', 'personal'])->orderBy('nombre_bien')->get(),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'id_bien' => ['required', 'integer', 'exists:bienes,id_bien'],
            'id_personal_nuevo' => ['nullable', 'integer', 'exists:personal,id_personal'],
            'id_area_nueva' => ['nullable', 'integer', 'exists:areas,id_area'],
            'tipo_movimiento' => ['required', 'in:' . self::TIPOS_MOVIMIENTO],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $bien = Bien::findOrFail($data['id_bien']);
        $esDevolucion = $data['tipo_movimiento'] === 'Devolucion';
        $nuevoPersonal = $esDevolucion ? null : ($data['id_personal_nuevo'] ?? $bien->id_personal);
        $nuevaArea = $esDevolucion ? null : ($data['id_area_nueva'] ?? $bien->id_area);

        HistorialAsignacion::create([
            'id_bien' => $bien->id_bien,
            'id_personal_anterior' => $bien->id_personal,
            'id_personal_nuevo' => $nuevoPersonal,
            'id_area_anterior' => $bien->id_area,
            'id_area_nueva' => $nuevaArea,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => $data['tipo_movimiento'],
            'observaciones' => $data['observaciones'] ?? 'Movimiento registrado',
        ]);

        $bien->update([
            'id_personal' => $nuevoPersonal,
            'id_area' => $nuevaArea,
            'estatus' => $esDevolucion ? 'Disponible' : ($nuevoPersonal || $nuevaArea ? 'Asignado' : $bien->estatus),
        ]);

        return redirect()->route('admin.historial')->with('success', 'Movimiento registrado correctamente.');
    }

    private function queryHistorial(Request $request): Builder
    {
        $search = $request->query('search');
        $tipo = $request->query('tipo');
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');

        return HistorialAsignacion::with(['bien', 'personalAnterior', 'personalNuevo', 'areaAnterior', 'areaNueva'])
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereHas('bien', fn(Builder $query) => $query
                        ->where('nombre_bien', 'like', "%{$search}%")
                        ->orWhere('no_inventario', 'like', "%{$search}%")
                        ->orWhere('codigo_barras', 'like', "%{$search}%"))
                        ->orWhereHas('personalAnterior', fn(Builder $query) => $query->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('personalNuevo', fn(Builder $query) => $query->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('areaAnterior', fn(Builder $query) => $query->where('nombre_area', 'like', "%{$search}%"))
                        ->orWhereHas('areaNueva', fn(Builder $query) => $query->where('nombre_area', 'like', "%{$search}%"))
                        ->orWhere('tipo_movimiento', 'like', "%{$search}%")
                        ->orWhere('observaciones', 'like', "%{$search}%");
                });
            })
            ->when($tipo, fn(Builder $query) => $query->where('tipo_movimiento', $tipo))
            ->when($fechaInicio, fn(Builder $query) => $query->whereDate('fecha_movimiento', '>=', $fechaInicio))
            ->when($fechaFin, fn(Builder $query) => $query->whereDate('fecha_movimiento', '<=', $fechaFin));
    }

    private function csvRow(array $columns): string
    {
        return implode(',', array_map(function ($value) {
            $value = (string) ($value ?? '');

            return '"' . str_replace('"', '""', $value) . '"';
        }, $columns)) . "\n";
    }

    private function simplePdf(array $lines): string
    {
        $linesPerPage = 50;
        $pages = array_chunk($lines, $linesPerPage);
        $pageCount = max(1, count($pages));

        $fontObject = 3 + $pageCount * 2;
        $contentObjectNumbers = [];
        $pageObjectNumbers = [];

        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjectNumbers[] = 3 + $i;
            $contentObjectNumbers[] = 3 + $pageCount + $i;
        }

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = implode(' ', array_map(fn($id) => "{$id} 0 R", $pageObjectNumbers));
        $objects[2] = "<< /Type /Pages /Kids [{$kids}] /Count {$pageCount} >>";

        foreach ($pages as $index => $pageLines) {
            $content = "BT\n/F1 10 Tf\n50 790 Td\n";
            $first = true;
            foreach ($pageLines as $line) {
                $line = mb_substr($line, 0, 110);
                $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
                $content .= ($first ? '' : "0 -14 Td\n") . "({$escaped}) Tj\n";
                $first = false;
            }
            $content .= "ET";

            $objects[$pageObjectNumbers[$index]] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents {$contentObjectNumbers[$index]} 0 R /Resources << /Font << /F1 {$fontObject} 0 R >> >> >>";
            $objects[$contentObjectNumbers[$index]] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";
        }

        $objects[$fontObject] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj {$body} endobj\n";
        }

        $xrefPosition = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($objects as $num => $body) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$num]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefPosition}\n%%EOF";

        return $pdf;
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Acceso denegado.');
        }
    }
}
