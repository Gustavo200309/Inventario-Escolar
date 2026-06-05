<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Personal;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportesController extends Controller
{
    public function index(): View
    {
        return view('admin.reportes', [
            'activeMenu' => 'reportes',
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
            'bienes' => Bien::count(),
            'personal' => Personal::count(),
        ]);
    }

    public function export(Request $request, string $format)
    {
        $format = strtolower($format);
        $bienes = Bien::with(['area', 'personal'])->orderBy('nombre_bien')->get();

        if ($format === 'pdf') {
            $content = "Reporte de inventario\n";
            foreach ($bienes as $bien) {
                $content .= sprintf("%s, %s, %s, %s\n", $bien->no_inventario, $bien->nombre_bien, $bien->estatus, $bien->area?->nombre_area ?: 'Sin área');
            }
            return Response::make($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reporte-inventario.pdf"',
            ]);
        }

        if ($format === 'excel') {
            $csv = "No. Inventario,Nombre del bien,Marca,Modelo,Estado,Área,Responsable\n";
            foreach ($bienes as $bien) {
                $csv .= sprintf('"%s","%s","%s","%s","%s","%s","%s"\n',
                    $bien->no_inventario,
                    $bien->nombre_bien,
                    $bien->marca,
                    $bien->modelo,
                    $bien->estatus,
                    $bien->area?->nombre_area ?: '',
                    $bien->personal?->nombre ?: ''
                );
            }

            return Response::streamDownload(fn() => print($csv), 'reporte-inventario.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        return redirect()->route('admin.reportes')->with('error', 'Formato de exportación no válido.');
    }
}
