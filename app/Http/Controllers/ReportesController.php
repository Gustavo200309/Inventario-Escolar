<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\Personal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportesController extends Controller
{
    public function index(Request $request): View
    {
        $bienes = $this->queryBienes($request)->get();

        return view('admin.reportes', [
            'activeMenu' => 'reportes',
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
            'estatuses' => Bien::query()->select('estatus')->distinct()->orderBy('estatus')->pluck('estatus')->filter(),
            'bienes' => $bienes,
            'totalBienes' => $bienes->count(),
            'valorTotal' => $bienes->sum(fn(Bien $bien) => (float) ($bien->valor ?? 0)),
            'porEstado' => $bienes->groupBy('estatus')->map->count(),
            'filters' => [
                'tipo' => $request->query('tipo', 'inventario'),
                'id_area' => $request->query('id_area'),
                'id_personal' => $request->query('id_personal'),
                'estatus' => $request->query('estatus'),
                'fecha_inicio' => $request->query('fecha_inicio'),
                'fecha_fin' => $request->query('fecha_fin'),
            ],
        ]);
    }

    public function export(Request $request, string $format)
    {
        $format = strtolower($format);
        $bienes = $this->queryBienes($request)->get();
        [$headers, $rows] = $this->buildRows($bienes);

        if ($format === 'excel' || $format === 'xlsx') {
            return $this->xlsxResponse($headers, $rows, 'reporte-inventario.xlsx');
        }

        if ($format === 'csv') {
            return $this->csvResponse($headers, $rows, 'reporte-inventario.csv');
        }

        if ($format === 'pdf') {
            $lines = [
                'Reporte de inventario',
                'Total de bienes: ' . $bienes->count(),
                'Valor total: $' . number_format($bienes->sum(fn(Bien $bien) => (float) ($bien->valor ?? 0)), 2),
                '',
            ];

            foreach ($rows->take(42) as $row) {
                $lines[] = sprintf('%s | %s | %s | %s', $row[0], $row[3], $row[6], $row[7] ?: 'Sin area');
            }

            return Response::make($this->simplePdf($lines), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reporte-inventario.pdf"',
            ]);
        }

        return redirect()->route('admin.reportes')->with('error', 'Formato de exportacion no valido.');
    }

    private function queryBienes(Request $request): Builder
    {
        $tipo = $request->query('tipo', 'inventario');
        $idArea = $request->query('id_area');
        $idPersonal = $request->query('id_personal');
        $estatus = $request->query('estatus');
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');

        return Bien::with(['area', 'personal'])
            ->when($tipo === 'pendientes', function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('estatus', 'Pendiente')
                        ->orWhereNull('id_personal')
                        ->orWhereNull('id_area');
                });
            })
            ->when($idArea, fn(Builder $query) => $query->where('id_area', $idArea))
            ->when($idPersonal, fn(Builder $query) => $query->where('id_personal', $idPersonal))
            ->when($estatus, fn(Builder $query) => $query->where('estatus', $estatus))
            ->when($fechaInicio, fn(Builder $query) => $query->whereDate('fecha_registro', '>=', $fechaInicio))
            ->when($fechaFin, fn(Builder $query) => $query->whereDate('fecha_registro', '<=', $fechaFin))
            ->orderBy('nombre_bien');
    }

    private function buildRows($bienes): array
    {
        $headers = [
            'No. Inventario',
            'Codigo de Barras',
            'ID SEP',
            'Nombre del bien',
            'Marca',
            'Modelo',
            'Estado',
            'Area',
            'Responsable',
            'Valor',
        ];

        $rows = $bienes->map(fn(Bien $bien) => [
            $bien->no_inventario,
            $bien->codigo_barras,
            $bien->id_sep,
            $bien->nombre_bien,
            $bien->marca,
            $bien->modelo,
            $bien->estatus,
            $bien->area?->nombre_area,
            $bien->personal?->nombre,
            number_format((float) ($bien->valor ?? 0), 2, '.', ''),
        ]);

        return [$headers, $rows];
    }

    private function csvResponse(array $headers, $rows, string $filename)
    {
        $csv = "\xEF\xBB\xBF" . $this->csvRow($headers);

        foreach ($rows as $row) {
            $csv .= $this->csvRow($row);
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function xlsxResponse(array $headers, $rows, string $filename)
    {
        if (! class_exists(\ZipArchive::class)) {
            return $this->csvResponse($headers, $rows, str_replace('.xlsx', '.csv', $filename));
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new \ZipArchive();

        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            return $this->csvResponse($headers, $rows, str_replace('.xlsx', '.csv', $filename));
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Inventario" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($headers, $rows));
        $zip->close();

        $content = file_get_contents($tmp);
        @unlink($tmp);

        return Response::make($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function worksheetXml(array $headers, $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $allRows = collect([$headers])->merge($rows);

        foreach ($allRows->values() as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $xml .= '<row r="' . $excelRow . '">';

            foreach (array_values($row) as $colIndex => $value) {
                $cell = $this->excelColumn($colIndex + 1) . $excelRow;
                $escaped = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $xml .= '<c r="' . $cell . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    private function excelColumn(int $index): string
    {
        $column = '';

        while ($index > 0) {
            $index--;
            $column = chr(65 + ($index % 26)) . $column;
            $index = intdiv($index, 26);
        }

        return $column;
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
        $content = "BT\n/F1 10 Tf\n50 790 Td\n";

        foreach ($lines as $index => $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], substr($line, 0, 110));
            $content .= ($index === 0 ? '' : "0 -14 Td\n") . "({$escaped}) Tj\n";
        }

        $content .= "ET";
        $length = strlen($content);

        return "%PDF-1.4\n"
            . "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            . "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            . "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n"
            . "4 0 obj << /Length {$length} >> stream\n{$content}\nendstream endobj\n"
            . "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
            . "trailer << /Root 1 0 R >>\n%%EOF";
    }
}
