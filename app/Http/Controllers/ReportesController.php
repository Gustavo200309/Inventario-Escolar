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
            $pdfRows = $this->buildPdfRows($bienes);

            return Response::make($this->inventoryPdf($request, $bienes, $pdfRows), 200, [
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
                    $query->whereIn('estatus', ['Pendiente', 'En revision', 'En mantenimiento', 'Danado'])
                        ->orWhere('estatus', 'like', '%revisi%')
                        ->orWhere('estatus', 'like', 'Da%ado')
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
            'ID SEP',
            'Nombre del bien',
            'Marca',
            'Modelo',
            'Area',
            'Estado',
            'Codigo de Barras',
            'Responsable',
            'Valor',
        ];

        $rows = $bienes->map(fn(Bien $bien) => [
            $bien->no_inventario,
            $bien->id_sep,
            $bien->nombre_bien,
            $bien->marca,
            $bien->modelo,
            $bien->area?->nombre_area,
            $bien->estatus,
            $bien->codigo_barras,
            $bien->personal?->nombre,
            number_format((float) ($bien->valor ?? 0), 2, '.', ''),
        ]);

        return [$headers, $rows];
    }

    private function buildPdfRows($bienes)
    {
        return $bienes->map(fn(Bien $bien) => [
            $bien->no_inventario,
            $bien->id_sep,
            $bien->nombre_bien,
            $bien->marca,
            $bien->modelo,
            $bien->area?->nombre_area,
            $bien->estatus,
            $bien->qr_svg,
            $bien->personal?->nombre,
            number_format((float) ($bien->valor ?? 0), 2, '.', ''),
        ]);
    }

    private function reportRowsWithoutValue(array $headers, $rows): array
    {
        $valueIndex = array_search('Valor', $headers, true);

        if ($valueIndex === false) {
            return [$headers, $rows];
        }

        unset($headers[$valueIndex]);

        $rows = $rows->map(function (array $row) use ($valueIndex) {
            unset($row[$valueIndex]);

            return array_values($row);
        });

        return [array_values($headers), $rows];
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

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Inventario" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
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
        $allRows = collect([$headers])->merge($rows)->values();
        $lastColumn = $this->excelColumn(count($headers));
        $lastRow = max(1, $allRows->count());
        $dimension = 'A1:' . $lastColumn . $lastRow;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="' . $dimension . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="20"/>'
            . $this->xlsxColumnsXml()
            . '<sheetData>';

        foreach ($allRows->values() as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $height = $excelRow === 1 ? 24 : 34;
            $style = $excelRow === 1 ? 1 : 2;
            $xml .= '<row r="' . $excelRow . '" ht="' . $height . '" customHeight="1">';

            foreach (array_values($row) as $colIndex => $value) {
                $cell = $this->excelColumn($colIndex + 1) . $excelRow;
                $cellStyle = $colIndex === 7 ? 3 : $style;
                $escaped = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $xml .= '<c r="' . $cell . '" s="' . $cellStyle . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml
            . '</sheetData>'
            . '<autoFilter ref="' . $dimension . '"/>'
            . '<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            . '</worksheet>';
    }

    private function xlsxColumnsXml(): string
    {
        $widths = [18, 16, 34, 18, 18, 26, 18, 32, 28, 14];
        $xml = '<cols>';

        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $xml .= '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"/>';
        }

        return $xml . '</cols>';
    }

    private function xlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1">'
            . '<numFmt numFmtId="164" formatCode="@"/>'
            . '</numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="11"/><color rgb="FF2F3E34"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF2F943C"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD8DDD4"/></left><right style="thin"><color rgb="FFD8DDD4"/></right><top style="thin"><color rgb="FFD8DDD4"/></top><bottom style="thin"><color rgb="FFD8DDD4"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
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

    private function inventoryPdf(Request $request, $bienes, $rows): string
    {
        $totalBienes = $bienes->count();
        $estados = $bienes->groupBy('estatus')->count();
        $tipo = $request->query('tipo', 'inventario') === 'pendientes' ? 'Bienes pendientes' : 'Inventario general';
        $filters = $this->pdfFilterSummary($request);

        $pages = [];
        $page = $this->pdfPageHeader($tipo, $totalBienes, $estados, $filters);
        $page .= $this->pdfTableHeader(392);

        $y = 350;
        $rowHeight = 42;
        $pageNumber = 1;

        if ($rows->isEmpty()) {
            $page .= $this->pdfNoRows($y);
        }

        foreach ($rows as $index => $row) {
            if ($y < 74) {
                $page .= $this->pdfFooter($pageNumber);
                $pages[] = $page;
                $pageNumber++;
                $page = $this->pdfPageHeader($tipo, $totalBienes, $estados, $filters);
                $page .= $this->pdfTableHeader(392);
                $y = 350;
            }

            $page .= $this->pdfInventoryRow($row, $y, $index % 2 === 0);
            $y -= $rowHeight;
        }

        $page .= $this->pdfFooter($pageNumber);
        $pages[] = $page;

        return $this->buildPdf($pages);
    }

    private function pdfFilterSummary(Request $request): string
    {
        $items = [];

        if ($request->query('id_area')) {
            $items[] = 'Area: ' . (Area::find($request->query('id_area'))?->nombre_area ?? 'Seleccionada');
        }

        if ($request->query('id_personal')) {
            $personal = Personal::find($request->query('id_personal'));
            $items[] = 'Responsable: ' . ($personal?->nombre ?? 'Seleccionado');
        }

        if ($request->query('estatus')) {
            $items[] = 'Estado: ' . $request->query('estatus');
        }

        if ($request->query('fecha_inicio') || $request->query('fecha_fin')) {
            $items[] = 'Periodo: ' . ($request->query('fecha_inicio') ?: 'Inicio') . ' a ' . ($request->query('fecha_fin') ?: 'Hoy');
        }

        return $items ? implode('  |  ', $items) : 'Sin filtros aplicados';
    }

    private function pdfPageHeader(string $tipo, int $totalBienes, int $estados, string $filters): string
    {
        $date = now()->format('d/m/Y H:i');

        return "0.933 0.945 0.925 rg 0 0 842 595 re f\n"
            . "0.976 0.980 0.965 rg 28 28 786 539 re f\n"
            . "0.184 0.580 0.235 rg 28 510 786 57 re f\n"
            . "0.129 0.412 0.173 rg 28 510 786 8 re f\n"
            . $this->pdfText('Sistema de Gestion de Inventario', 48, 543, 18, true, '1 1 1')
            . $this->pdfText('Reporte de Inventario', 48, 524, 11, false, '0.890 0.965 0.902')
            . $this->pdfText('Generado: ' . $date, 690, 543, 9, false, '0.890 0.965 0.902')
            . $this->pdfText($tipo, 48, 485, 16, true, '0.122 0.373 0.169')
            . $this->pdfText($this->truncateText($filters, 126), 48, 468, 9, false, '0.427 0.455 0.420')
            . $this->pdfMetricCard(48, 420, 344, 'Total bienes', (string) $totalBienes)
            . $this->pdfMetricCard(420, 420, 344, 'Estados', (string) $estados);
    }

    private function pdfMetricCard(int $x, int $y, int $w, string $label, string $value): string
    {
        return "1 1 1 rg {$x} {$y} {$w} 58 re f\n"
            . "0.847 0.867 0.831 RG {$x} {$y} {$w} 58 re S\n"
            . $this->pdfText($label, $x + 16, $y + 36, 9, true, '0.427 0.455 0.420')
            . $this->pdfText($value, $x + 16, $y + 14, 18, true, '0.071 0.565 0.188');
    }

    private function pdfTableHeader(int $y): string
    {
        return "0.953 0.965 0.945 rg 38 {$y} 766 25 re f\n"
            . "0.894 0.933 0.886 RG 38 {$y} 766 25 re S\n"
            . $this->pdfText('No. inv.', 44, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('ID SEP', 112, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Bien', 158, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Marca / modelo', 286, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Area', 395, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Estado', 490, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Codigo QR', 555, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Responsable', 660, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Valor', 760, $y + 9, 7, true, '0.184 0.314 0.204');
    }

    private function pdfInventoryRow(array $row, int $y, bool $shade): string
    {
        $bg = $shade ? '0.984 0.992 0.976' : '1 1 1';
        $marcaModelo = trim(($row[3] ?: 'Sin marca') . ' / ' . ($row[4] ?: 'Sin modelo'));
        $qrSvg = $row[7] ?? null;

        return "{$bg} rg 38 {$y} 766 39 re f\n"
            . "0.914 0.933 0.902 RG 38 {$y} 766 39 re S\n"
            . $this->pdfText($this->truncateText($row[0] ?: 'Sin dato', 15), 44, $y + 18, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[1] ?: 'N/A', 10), 112, $y + 18, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[2] ?: 'Sin nombre', 24), 158, $y + 18, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($marcaModelo, 20), 286, $y + 18, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[5] ?: 'Sin area', 17), 395, $y + 18, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[6] ?: 'Sin estado', 12), 490, $y + 18, 6.6, false, '0.071 0.565 0.188')
            . $this->pdfQrGraphic((string) ($qrSvg ?: ''), 555, $y + 6, 26)
            . $this->pdfText($this->truncateText($row[8] ?: 'Sin responsable', 19), 660, $y + 18, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText('$' . $this->truncateText($row[9] ?: '0.00', 9), 760, $y + 18, 6.4, false, '0.184 0.243 0.204');
    }

    private function pdfQrGraphic(string $svg, int $x, int $y, int $size): string
    {
        if (trim($svg) === '') {
            return $this->pdfText('N/A', $x + 6, $y + 10, 6, false, '0.427 0.455 0.420');
        }

        $dimensions = $this->pdfSvgDimensions($svg);
        if ($dimensions === null) {
            return $this->pdfText('N/A', $x + 6, $y + 10, 6, false, '0.427 0.455 0.420');
        }

        [$viewWidth, $viewHeight] = $dimensions;
        if ($viewWidth <= 0 || $viewHeight <= 0) {
            return $this->pdfText('N/A', $x + 6, $y + 10, 6, false, '0.427 0.455 0.420');
        }

        $scale = min($size / $viewWidth, $size / $viewHeight);
        $drawWidth = $viewWidth * $scale;
        $drawHeight = $viewHeight * $scale;
        $offsetX = $x + (($size - $drawWidth) / 2);
        $offsetY = $y + (($size - $drawHeight) / 2);

        $pdf = "1 1 1 rg {$x} {$y} {$size} {$size} re f\n"
            . "0.847 0.867 0.831 RG {$x} {$y} {$size} {$size} re S\n";

        $paths = $this->pdfExtractSvgPaths($svg);
        foreach ($paths as $pathData) {
            $stream = $this->pdfSvgPathToStream($pathData['d'], $offsetX, $offsetY, $scale, $viewHeight, $pathData['transform']);
            if ($stream !== '') {
                $pdf .= "0.071 0.071 0.071 rg\n" . $stream;
            }
        }

        return $pdf;
    }

    private function pdfSvgDimensions(string $svg): ?array
    {
        if (! class_exists(\DOMDocument::class)) {
            return null;
        }

        $document = new \DOMDocument();
        if (! @$document->loadXML($svg)) {
            return null;
        }

        $root = $document->documentElement;
        if (! $root) {
            return null;
        }

        $width = (float) ($root->getAttribute('width') ?: 0);
        $height = (float) ($root->getAttribute('height') ?: 0);

        if ($width > 0 && $height > 0) {
            return [$width, $height];
        }

        $viewBox = preg_split('/\s+/', trim($root->getAttribute('viewBox')));
        if (is_array($viewBox) && count($viewBox) === 4) {
            return [(float) $viewBox[2], (float) $viewBox[3]];
        }

        return null;
    }

    private function pdfExtractSvgPaths(string $svg): array
    {
        if (! class_exists(\DOMDocument::class)) {
            return [];
        }

        $document = new \DOMDocument();
        if (! @$document->loadXML($svg)) {
            return [];
        }

        $paths = [];

        $root = $document->documentElement;
        if ($root instanceof \DOMElement) {
            $this->pdfCollectSvgPaths($root, ['sx' => 1.0, 'sy' => 1.0, 'tx' => 0.0, 'ty' => 0.0], $paths);
        }

        return $paths;
    }

    private function pdfCollectSvgPaths(\DOMElement $element, array $matrix, array &$paths): void
    {
        $currentMatrix = $matrix;
        $transform = trim((string) $element->getAttribute('transform'));
        if ($transform !== '') {
            $currentMatrix = $this->pdfComposeSvgTransform($currentMatrix, $transform);
        }

        if ($element->tagName === 'path') {
            $d = trim((string) $element->getAttribute('d'));
            if ($d !== '') {
                $paths[] = [
                    'd' => $d,
                    'transform' => $currentMatrix,
                ];
            }
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $this->pdfCollectSvgPaths($child, $currentMatrix, $paths);
            }
        }
    }

    private function pdfComposeSvgTransform(array $matrix, string $transform): array
    {
        if (preg_match_all('/(scale|translate)\(([^\)]*)\)/i', $transform, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = strtolower($match[1]);
                $values = array_values(array_filter(array_map('trim', preg_split('/[,\s]+/', trim($match[2]))), fn($value) => $value !== ''));

                if ($type === 'scale') {
                    $factorX = isset($values[0]) ? (float) $values[0] : 1.0;
                    $factorY = isset($values[1]) ? (float) $values[1] : $factorX;
                    $matrix['sx'] *= $factorX;
                    $matrix['sy'] *= $factorY;
                    $matrix['tx'] *= $factorX;
                    $matrix['ty'] *= $factorY;
                }

                if ($type === 'translate') {
                    $translateX = isset($values[0]) ? (float) $values[0] : 0.0;
                    $translateY = isset($values[1]) ? (float) $values[1] : 0.0;
                    $matrix['tx'] += $translateX;
                    $matrix['ty'] += $translateY;
                }
            }
        }

        return $matrix;
    }

    private function pdfSvgPathToStream(string $pathData, float $offsetX, float $offsetY, float $scale, float $viewHeight, array $matrix): string
    {
        preg_match_all('/[MmLlHhVvZz]|-?\d*\.?\d+(?:e[-+]?\d+)?/i', $pathData, $matches);
        $tokens = $matches[0] ?? [];
        if ($tokens === []) {
            return '';
        }

        $pdf = '';
        $index = 0;
        $currentX = 0.0;
        $currentY = 0.0;
        $subpathStartX = 0.0;
        $subpathStartY = 0.0;
        $moduleScaleX = $matrix['sx'] ?? 1.0;
        $moduleScaleY = $matrix['sy'] ?? 1.0;
        $moduleTranslateX = $matrix['tx'] ?? 0.0;
        $moduleTranslateY = $matrix['ty'] ?? 0.0;
        $currentCommand = null;

        $nextNumber = function () use (&$tokens, &$index): ?float {
            while ($index < count($tokens)) {
                $value = trim((string) $tokens[$index++]);
                if ($value === '') {
                    continue;
                }

                if (preg_match('/^-?\d+(?:\.\d+)?(?:e[-+]?\d+)?$/i', $value)) {
                    return (float) $value;
                }

                return null;
            }

            return null;
        };

        while ($index < count($tokens)) {
            $token = trim((string) $tokens[$index++]);
            if ($token === '') {
                continue;
            }

            if (preg_match('/^[MmLlHhVvZz]$/', $token)) {
                $currentCommand = $token;
                if ($currentCommand === 'Z' || $currentCommand === 'z') {
                    [$pdfX, $pdfY] = $this->pdfTransformPoint($subpathStartX, $subpathStartY, $offsetX, $offsetY, $scale, $viewHeight, $moduleScaleX, $moduleScaleY, $moduleTranslateX, $moduleTranslateY);
                    $pdf .= $pdfX . ' ' . $pdfY . " l\n";
                    $pdf .= "h\n";
                    continue;
                }
            } else {
                if ($currentCommand === null) {
                    continue;
                }

                --$index;
            }

            if ($currentCommand === null) {
                continue;
            }

            if ($currentCommand === 'M' || $currentCommand === 'm') {
                $x = $nextNumber();
                $y = $nextNumber();
                if ($x === null || $y === null) {
                    break;
                }

                if ($currentCommand === 'm') {
                    $currentX += $x;
                    $currentY += $y;
                } else {
                    $currentX = $x;
                    $currentY = $y;
                }

                $subpathStartX = $currentX;
                $subpathStartY = $currentY;

                [$pdfX, $pdfY] = $this->pdfTransformPoint($currentX, $currentY, $offsetX, $offsetY, $scale, $viewHeight, $moduleScaleX, $moduleScaleY, $moduleTranslateX, $moduleTranslateY);
                $pdf .= $pdfX . ' ' . $pdfY . " m\n";
                continue;
            }

            if ($currentCommand === 'L' || $currentCommand === 'l') {
                while ($index < count($tokens) && ! preg_match('/^[MmLlHhVvZz]$/', (string) $tokens[$index])) {
                    $x = $nextNumber();
                    $y = $nextNumber();
                    if ($x === null || $y === null) {
                        break;
                    }

                    if ($currentCommand === 'l') {
                        $currentX += $x;
                        $currentY += $y;
                    } else {
                        $currentX = $x;
                        $currentY = $y;
                    }

                    [$pdfX, $pdfY] = $this->pdfTransformPoint($currentX, $currentY, $offsetX, $offsetY, $scale, $viewHeight, $moduleScaleX, $moduleScaleY, $moduleTranslateX, $moduleTranslateY);
                    $pdf .= $pdfX . ' ' . $pdfY . " l\n";
                }

                continue;
            }

            if ($currentCommand === 'H' || $currentCommand === 'h' || $currentCommand === 'V' || $currentCommand === 'v') {
                while ($index < count($tokens) && ! preg_match('/^[MmLlHhVvZz]$/', (string) $tokens[$index])) {
                    $value = $nextNumber();
                    if ($value === null) {
                        break;
                    }

                    if ($currentCommand === 'H' || $currentCommand === 'h') {
                        $currentX = $currentCommand === 'h' ? $currentX + $value : $value;
                    } else {
                        $currentY = $currentCommand === 'v' ? $currentY + $value : $value;
                    }

                    [$pdfX, $pdfY] = $this->pdfTransformPoint($currentX, $currentY, $offsetX, $offsetY, $scale, $viewHeight, $moduleScaleX, $moduleScaleY, $moduleTranslateX, $moduleTranslateY);
                    $pdf .= $pdfX . ' ' . $pdfY . " l\n";
                }

                continue;
            }
        }

        if ($pdf !== '') {
            $pdf .= "f*\n";
        }

        return $pdf;
    }

    private function pdfTransformPoint(float $x, float $y, float $offsetX, float $offsetY, float $scale, float $viewHeight, float $moduleScaleX = 1.0, float $moduleScaleY = 1.0, float $moduleTranslateX = 0.0, float $moduleTranslateY = 0.0): array
    {
        $x = ($x * $moduleScaleX) + $moduleTranslateX;
        $y = ($y * $moduleScaleY) + $moduleTranslateY;
        $pdfX = $offsetX + ($x * $scale);
        $pdfY = $offsetY + (($viewHeight - $y) * $scale);

        return [round($pdfX, 3), round($pdfY, 3)];
    }

    private function pdfNoRows(int $y): string
    {
        return "1 1 1 rg 38 " . ($y - 18) . " 766 42 re f\n"
            . "0.914 0.933 0.902 RG 38 " . ($y - 18) . " 766 42 re S\n"
            . $this->pdfText('No hay bienes para los filtros seleccionados', 310, $y + 5, 10, false, '0.184 0.243 0.204');
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
}
