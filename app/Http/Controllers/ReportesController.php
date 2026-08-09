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
    private array $pdfImageResources = [];

    public function index(Request $request): View
    {
        $perPage = (int) $request->query('per_page', 25);
        $allowedPerPage = [10, 20, 25, 50];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }

        $query = $this->queryBienes($request);

        $bienes = (clone $query)->paginate($perPage)->withQueryString();

        return view('admin.reportes', [
            'activeMenu' => 'reportes',
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
            'personals' => Personal::where('estatus', 'Activo')->orderBy('nombre')->get(),
            'estatuses' => Bien::query()->select('estatus')->distinct()->orderBy('estatus')->pluck('estatus')->filter(),
            'bienes' => $bienes,
            'totalBienes' => (clone $query)->count(),
            'valorTotal' => (clone $query)->sum('valor'),
            'porEstado' => (clone $query)
                ->selectRaw('estatus, COUNT(*) as total')
                ->groupBy('estatus')
                ->pluck('total', 'estatus'),
            'perPage' => $perPage,
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
        set_time_limit(300);
        $format = strtolower($format);

        if ($format === 'excel' || $format === 'xlsx') {
            $bienes = $this->queryBienes($request)->get();
            [$headers, $rows] = $this->buildRows($bienes);

            return $this->xlsxResponse($headers, $rows, 'reporte-inventario.xlsx');
        }

        if ($format === 'csv') {
            $bienes = $this->queryBienes($request)->get();
            [$headers, $rows] = $this->buildRows($bienes);

            return $this->csvResponse($headers, $rows, 'reporte-inventario.csv');
        }

        if ($format === 'pdf') {
            $bienes = $this->queryBienes($request, [
                'id_bien',
                'no_inventario',
                'id_sep',
                'nombre_bien',
                'marca',
                'modelo',
                'id_area',
                'id_personal',
                'estatus',
                'fecha_registro',
            ])->get();
            $pdfRows = $this->buildPdfRows($bienes);

            return Response::make($this->inventoryPdf($request, $bienes, $pdfRows), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reporte-inventario.pdf"',
            ]);
        }

        return redirect()->route('admin.reportes')->with('error', 'Formato de exportacion no valido.');
    }

    private function queryBienes(Request $request, ?array $columns = null): Builder
    {
        $tipo = $request->query('tipo', 'inventario');
        $idArea = $request->query('id_area');
        $idPersonal = $request->query('id_personal');
        $estatus = $request->query('estatus');
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');

        $query = Bien::query()
            ->with([
                'area:id_area,nombre_area',
                'personal:id_personal,nombre,apellido_paterno,apellido_materno',
            ]);

        if ($columns !== null) {
            $query->select($columns);
        }

        return $query
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
            $bien->personal?->nombre_completo,
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
            $bien->personal?->nombre_completo,
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
        $filters = $this->pdfFilterSummary($request);

        $pages = [];
        $page = $this->pdfPageHeader($totalBienes, $filters);
        $page .= $this->pdfTableHeader(432);

        $y = 398;
        $rowHeight = 31;
        $pageNumber = 1;

        if ($rows->isEmpty()) {
            $page .= $this->pdfNoRows($y);
        }

        foreach ($rows as $index => $row) {
            if ($y < 68) {
                $page .= $this->pdfFooter($pageNumber);
                $pages[] = $page;
                $pageNumber++;
                $page = $this->pdfPageHeader($totalBienes, $filters);
                $page .= $this->pdfTableHeader(432);
                $y = 398;
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
            $items[] = 'Responsable: ' . ($personal?->nombre_completo ?? 'Seleccionado');
        }

        if ($request->query('estatus')) {
            $items[] = 'Estado: ' . $request->query('estatus');
        }

        if ($request->query('fecha_inicio') || $request->query('fecha_fin')) {
            $items[] = 'Periodo: ' . ($request->query('fecha_inicio') ?: 'Inicio') . ' a ' . ($request->query('fecha_fin') ?: 'Hoy');
        }

        return $items ? implode('  |  ', $items) : 'Sin filtros aplicados';
    }

    private function pdfPageHeader(int $totalBienes, string $filters): string
    {
        $date = now()->format('d/m/Y H:i');

        return "0.933 0.945 0.925 rg 0 0 842 595 re f\n"
            . "0.976 0.980 0.965 rg 28 28 786 539 re f\n"
            . "0.184 0.580 0.235 rg 28 510 786 57 re f\n"
            . "0.129 0.412 0.173 rg 28 510 786 8 re f\n"
            . $this->pdfText('Sistema de Gestion de Inventario', 48, 543, 18, true, '1 1 1')
            . $this->pdfText('Reporte de Inventario', 48, 524, 11, false, '0.890 0.965 0.902')
            . $this->pdfText('Generado: ' . $date, 48, 507, 9, false, '0.427 0.455 0.420')
            . $this->pdfText('Total bienes: ' . $totalBienes, 48, 485, 11, true, '0.122 0.373 0.169')
            . $this->pdfText($this->truncateText($filters, 126), 48, 468, 9, false, '0.427 0.455 0.420')
            . $this->pdfHeaderLogos();
    }

    private function pdfHeaderLogos(): string
    {
        // Give the logos more presence and keep them visually balanced.
        $logos = [
            ['name' => 'Im1', 'path' => public_path('images/logo_cbta.png'), 'x' => 476, 'y' => 512, 'w' => 64, 'h' => 52],
            ['name' => 'Im2', 'path' => public_path('images/logo_2_oscuro.png'), 'x' => 548, 'y' => 516, 'w' => 128, 'h' => 46],
            ['name' => 'Im3', 'path' => public_path('images/logo_3_oscuro.png'), 'x' => 684, 'y' => 516, 'w' => 122, 'h' => 46],
        ];

        $commands = '';

        foreach ($logos as $logo) {
            if ($this->pdfRegisterImage($logo['name'], $logo['path'])) {
                $resource = $this->pdfImageResources[$logo['name']];
                $scale = min($logo['w'] / $resource['width'], $logo['h'] / $resource['height']);
                $width = max(1, (int) round($resource['width'] * $scale));
                $height = max(1, (int) round($resource['height'] * $scale));
                $x = $logo['x'] + (int) round(($logo['w'] - $width) / 2);
                $y = $logo['y'] + (int) round(($logo['h'] - $height) / 2);
                $commands .= $this->pdfImageCommand($logo['name'], $x, $y, $width, $height);
            }
        }

        return $commands;
    }
    private function pdfRegisterImage(string $name, string $path): bool
    {
        if (isset($this->pdfImageResources[$name])) {
            return true;
        }

        if (! is_file($path) || ! function_exists('imagecreatefrompng')) {
            return false;
        }

        $source = @imagecreatefrompng($path);
        if (! $source) {
            return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $cropMinX = $width;
        $cropMinY = $height;
        $cropMaxX = -1;
        $cropMaxY = -1;
        $threshold = 244;

        for ($scanY = 0; $scanY < $height; $scanY++) {
            for ($scanX = 0; $scanX < $width; $scanX++) {
                $pixel = imagecolorat($source, $scanX, $scanY);
                $red = ($pixel >> 16) & 0xFF;
                $green = ($pixel >> 8) & 0xFF;
                $blue = $pixel & 0xFF;

                if ($red < $threshold || $green < $threshold || $blue < $threshold) {
                    $cropMinX = min($cropMinX, $scanX);
                    $cropMinY = min($cropMinY, $scanY);
                    $cropMaxX = max($cropMaxX, $scanX);
                    $cropMaxY = max($cropMaxY, $scanY);
                }
            }
        }

        if ($cropMaxX >= 0 && $cropMaxY >= 0) {
            $padding = 10;
            $cropMinX = max(0, $cropMinX - $padding);
            $cropMinY = max(0, $cropMinY - $padding);
            $cropMaxX = min($width - 1, $cropMaxX + $padding);
            $cropMaxY = min($height - 1, $cropMaxY + $padding);
            $copyWidth = $cropMaxX - $cropMinX + 1;
            $copyHeight = $cropMaxY - $cropMinY + 1;
        } else {
            $cropMinX = 0;
            $cropMinY = 0;
            $copyWidth = $width;
            $copyHeight = $height;
        }

        $background = imagecreatetruecolor($copyWidth, $copyHeight);

        $backgroundColor = imagecolorallocate($background, 47, 148, 60);
        imagefilledrectangle($background, 0, 0, $copyWidth, $copyHeight, $backgroundColor);
        imagealphablending($background, true);
        imagecopy($background, $source, 0, 0, $cropMinX, $cropMinY, $copyWidth, $copyHeight);
        // Keep only the visible mark and blend the pale canvas into the header green.
        for ($y = 0; $y < $copyHeight; $y++) {
            for ($x = 0; $x < $copyWidth; $x++) {
                $pixel = imagecolorat($background, $x, $y);
                $red = ($pixel >> 16) & 0xFF;
                $green = ($pixel >> 8) & 0xFF;
                $blue = $pixel & 0xFF;

                if ($red >= $threshold && $green >= $threshold && $blue >= $threshold) {
                    imagesetpixel($background, $x, $y, $backgroundColor);
                }
            }
        }

        ob_start();
        imagejpeg($background, null, 92);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($background);

        if ($jpeg === false || $jpeg === '') {
            return false;
        }

        $this->pdfImageResources[$name] = [
            'data' => $jpeg,
            'width' => $copyWidth,
            'height' => $copyHeight,
        ];

        return true;
    }
    private function pdfImageCommand(string $name, int $x, int $y, int $width, int $height): string
    {
        return "q {$width} 0 0 {$height} {$x} {$y} cm /{$name} Do Q\n";
    }

    private function pdfTableHeader(int $y): string
    {
        return "0.953 0.965 0.945 rg 38 {$y} 766 25 re f\n"
            . "0.894 0.933 0.886 RG 38 {$y} 766 25 re S\n"
            . $this->pdfText('No. inv.', 44, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('ID SEP', 130, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Bien', 190, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Marca / modelo', 335, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Area', 470, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Estado', 580, $y + 9, 7, true, '0.184 0.314 0.204')
            . $this->pdfText('Responsable', 660, $y + 9, 7, true, '0.184 0.314 0.204');
    }

    private function pdfInventoryRow(array $row, int $y, bool $shade): string
    {
        $bg = $shade ? '0.984 0.992 0.976' : '1 1 1';
        $marcaModelo = trim(($row[3] ?: 'Sin marca') . ' / ' . ($row[4] ?: 'Sin modelo'));

        return "{$bg} rg 38 {$y} 766 28 re f\n"
            . "0.914 0.933 0.902 RG 38 {$y} 766 28 re S\n"
            . $this->pdfText($this->truncateText($row[0] ?: 'Sin dato', 18), 44, $y + 11, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[1] ?: 'N/A', 14), 130, $y + 11, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[2] ?: 'Sin nombre', 28), 190, $y + 11, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($marcaModelo, 24), 335, $y + 11, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[5] ?: 'Sin area', 20), 470, $y + 11, 6.6, false, '0.184 0.243 0.204')
            . $this->pdfText($this->truncateText($row[6] ?: 'Sin estado', 14), 580, $y + 11, 6.6, false, '0.071 0.565 0.188')
            . $this->pdfText($this->truncateText($row[7] ?: 'Sin responsable', 24), 660, $y + 11, 6.6, false, '0.184 0.243 0.204');
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
        $imageResources = array_values($this->pdfImageResources);
        $imageObjectStart = 3 + (count($pages) * 2);
        $fontRegularObject = $imageObjectStart + count($imageResources);
        $fontBoldObject = $fontRegularObject + 1;

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        foreach ($pages as $index => $content) {
            $pageObject = 3 + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageRefs[] = "{$pageObject} 0 R";
            $resourceXObjects = '';

            foreach ($imageResources as $imageIndex => $resource) {
                $resourceName = 'Im' . ($imageIndex + 1);
                $resourceObject = $imageObjectStart + $imageIndex;
                $resourceXObjects .= " /{$resourceName} {$resourceObject} 0 R";
            }

            $xObjectResources = $resourceXObjects !== '' ? " /XObject <<{$resourceXObjects} >>" : '';
            $objects[$pageObject] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Contents {$contentObject} 0 R /Resources << /Font << /F1 {$fontRegularObject} 0 R /F2 {$fontBoldObject} 0 R >>{$xObjectResources} >> >>";
            $objects[$contentObject] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";
        }

        foreach ($imageResources as $imageIndex => $resource) {
            $objectNumber = $imageObjectStart + $imageIndex;
            $length = strlen($resource['data']);

            $objects[$objectNumber] = "<< /Type /XObject /Subtype /Image /Width {$resource['width']} /Height {$resource['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$length} >>\nstream\n" . $resource['data'] . "\nendstream";
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
        $text = str_replace(['Ã¡', 'Ã©', 'Ã­', 'Ã³', 'Ãº', 'Ã±', 'Ã', 'Ã‰', 'Ã', 'Ã“', 'Ãš', 'Ã‘'], ['a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N'], $text);

        return preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
    }

    private function truncateText(string $text, int $length): string
    {
        $text = trim($this->normalizePdfText($text));

        return strlen($text) > $length ? substr($text, 0, $length - 3) . '...' : $text;
    }
}
