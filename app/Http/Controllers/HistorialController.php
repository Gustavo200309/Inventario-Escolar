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

    public function index(Request $request): View
    {
        $historiales = $this->queryHistorial($request)
            ->orderBy('fecha_movimiento', 'desc')
            ->get();

        return view('admin.historial', [
            'historiales' => $historiales,
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
                    $historial->personalAnterior?->nombre,
                    $historial->personalNuevo?->nombre,
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
            $rows = $historiales->map(fn(HistorialAsignacion $historial) => [
                $historial->tipo_movimiento,
                $historial->fecha_movimiento?->format('d/m/Y H:i') ?: 'Sin fecha',
                $historial->bien?->nombre_bien ?: 'Sin bien',
                $historial->bien?->no_inventario ?: 'N/A',
                $historial->personalAnterior?->nombre ?: 'Sin responsable',
                $historial->personalNuevo?->nombre ?: 'Sin responsable',
                $historial->areaAnterior?->nombre_area ?: 'Sin area',
                $historial->areaNueva?->nombre_area ?: 'Sin area',
                $historial->observaciones ?: 'Sin observaciones',
            ]);

            return Response::make($this->historialPdf($rows), 200, [
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
            'observaciones' => ['nullable', 'string'],
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

    private function historialPdf($rows): string
    {
        $pages = [];
        $page = $this->pdfPageHeader('Historial de movimientos', $rows->count());
        $page .= $this->pdfTableHeader(392);

        $y = 350;
        $rowHeight = 39;

        if ($rows->isEmpty()) {
            $page .= $this->pdfNoRows($y);
        }

        foreach ($rows as $index => $row) {
            if ($y < 74) {
                $page .= $this->pdfFooter(1 + count($pages));
                $pages[] = $page;
                $page = $this->pdfPageHeader('Historial de movimientos', $rows->count());
                $page .= $this->pdfTableHeader(392);
                $y = 350;
            }

            $page .= $this->pdfRow($row, $y, $index % 2 === 0);
            $y -= $rowHeight;
        }

        $page .= $this->pdfFooter(1 + count($pages));
        $pages[] = $page;

        return $this->buildPdf($pages);
    }

    private function pdfPageHeader(string $title, int $totalMovements): string
    {
        $date = now()->format('d/m/Y H:i');

        return "0.933 0.945 0.925 rg 0 0 842 595 re f\n"
            . "0.976 0.980 0.965 rg 28 28 786 539 re f\n"
            . "0.184 0.580 0.235 rg 28 510 786 57 re f\n"
            . "0.129 0.412 0.173 rg 28 510 786 8 re f\n"
            . $this->pdfText('Sistema de Gestion de Inventario', 48, 543, 18, true, '1 1 1')
            . $this->pdfText('Reporte de Historial', 48, 524, 11, false, '0.890 0.965 0.902')
            . $this->pdfText('Generado: ' . $date, 690, 543, 9, false, '0.890 0.965 0.902')
            . $this->pdfText($title, 48, 485, 16, true, '0.122 0.373 0.169')
            . $this->pdfText('Total movimientos: ' . $totalMovements, 48, 468, 9, false, '0.427 0.455 0.420')
            . $this->pdfText('Filtros del historial', 670, 468, 9, false, '0.427 0.455 0.420');
    }

    private function pdfTableHeader(int $y): string
    {
        return "0.953 0.965 0.945 rg 38 {$y} 766 25 re f\n"
            . "0.894 0.933 0.886 RG 38 {$y} 766 25 re S\n"
            . $this->pdfText('Tipo', 44, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Fecha', 106, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Bien', 178, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('No. inv.', 298, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Resp. anterior', 356, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Resp. nuevo', 444, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Area ant.', 532, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Area nueva', 604, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Observaciones', 680, $y + 9, 7, true, '0.184 0.314 0.204');
    }

    private function pdfRow(array $row, int $y, bool $shade): string
    {
        $bg = $shade ? '0.984 0.992 0.976' : '1 1 1';

        return "{$bg} rg 38 {$y} 766 39 re f\n"
            . "0.914 0.933 0.902 RG 38 {$y} 766 39 re S\n"
            . $this->pdfText($this->truncateText($row[0] ?: 'Sin tipo', 14), 44, $y + 18, 6.2, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[1] ?: 'Sin fecha', 16), 106, $y + 18, 6.2, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[2] ?: 'Sin bien', 22), 178, $y + 18, 6.2, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[3] ?: 'N/A', 10), 298, $y + 18, 6.2, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[4] ?: 'Sin responsable', 14), 356, $y + 18, 6.2, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[5] ?: 'Sin responsable', 14), 444, $y + 18, 6.2, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[6] ?: 'Sin area', 12), 532, $y + 18, 6.2, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[7] ?: 'Sin area', 12), 604, $y + 18, 6.2, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[8] ?: 'Sin observaciones', 18), 680, $y + 18, 6.2, false, '0.184 0.243 0.204');
    }

    private function pdfNoRows(int $y): string
    {
        return "1 1 1 rg 38 " . ($y - 18) . " 766 42 re f\n"
            . "0.914 0.933 0.902 RG 38 " . ($y - 18) . " 766 42 re S\n"
            . $this->pdfText('No hay movimientos para los filtros seleccionados', 270, $y + 5, 10, false, '0.184 0.243 0.204');
    }

    private function pdfFooter(int $pageNumber): string
    {
        return "0.847 0.867 0.831 RG 48 42 716 0 re S\n"
            . $this->pdfText('Inventario Escolar', 48, 28, 8, false, '0.427 0.455 0.420')
            . $this->pdfText('Pagina ' . $pageNumber, 724, 28, 8, false, '0.427 0.455 0.420');
    }

    private function buildPdf(array $pages): string
    {
        $objects = [];
        $pageRefs = [];
        $fontRegularObject = 3 + (count($pages) * 2);
        $fontBoldObject = $fontRegularObject + 1;

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        foreach ($pages as $index => $content) {
            $pageObject = 3 + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageRefs[] = "{$pageObject} 0 R";
            $objects[$pageObject] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Contents {$contentObject} 0 R /Resources << /Font << /F1 {$fontRegularObject} 0 R /F2 {$fontBoldObject} 0 R >> >> >>";
            $objects[$contentObject] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pages) . ' >>';
        $objects[$fontRegularObject] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$fontBoldObject] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function pdfText(string $text, int $x, int $y, float $size, bool $bold = false, string $color = '0 0 0'): string
    {
        $font = $bold ? 'F2' : 'F1';
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->normalizePdfText($text));

        return "{$color} rg BT /{$font} {$size} Tf {$x} {$y} Td ({$escaped}) Tj ET\n";
    }

    private function normalizePdfText(string $text): string
    {
        $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N'], $text);

        return preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
    }

    private function truncateText(string $text, int $length): string
    {
        $text = trim($this->normalizePdfText($text));

        return strlen($text) > $length ? substr($text, 0, $length - 3) . '...' : $text;
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, 'Acceso denegado.');
        }
    }
}
