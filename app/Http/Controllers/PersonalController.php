<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ImportableExcel;
use App\Models\Area;
use App\Models\Bien;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class PersonalController extends Controller
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

        $personals = Personal::with('area')
            ->withCount('bienes')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('nombre', 'like', "%{$search}%")
                ->orWhere('apellido_paterno', 'like', "%{$search}%")
                ->orWhere('apellido_materno', 'like', "%{$search}%")
                ->orWhere('puesto', 'like', "%{$search}%")
            ))
            ->orderBy('nombre')
            ->get();

        return view('admin.personal', [
            'personals' => $personals,
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
            'search' => $search,
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.personal-create', [
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'min:2', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'puesto' => ['required', 'string', 'min:2', 'max:100'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\+\-\(\)\s]*$/'],
            'id_area' => ['nullable', 'integer', 'exists:areas,id_area'],
            'estatus' => ['required', 'in:Activo,Inactivo'],
        ]);

        Personal::create(array_merge($data, [
            'fecha_registro' => now(),
        ]));

        return redirect()->route('admin.personal')->with('success', 'Personal registrado correctamente.');
    }

    public function show(Personal $personal): View
    {
        return view('admin.personal-show', [
            'personal' => $personal->load(['area', 'bienes']),
        ]);
    }

    public function edit(Personal $personal): View
    {
        $this->authorizeAdmin();

        return view('admin.personal-edit', [
            'personal' => $personal,
            'areas' => Area::where('estatus', 'Activa')->orderBy('nombre_area')->get(),
        ]);
    }

    public function update(Request $request, Personal $personal)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'min:2', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'puesto' => ['required', 'string', 'min:2', 'max:100'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\+\-\(\)\s]*$/'],
            'id_area' => ['nullable', 'integer', 'exists:areas,id_area'],
            'estatus' => ['required', 'in:Activo,Inactivo'],
        ]);

        $personal->update($data);

        return redirect()->route('admin.personal')->with('success', 'Personal actualizado correctamente.');
    }

    public function destroy(Personal $personal)
    {
        $this->authorizeAdmin();

        // La clave foranea deja el bien sin responsable; si ademas queda sin area,
        // no puede seguir marcado como "Asignado".
        $afectados = Bien::withEliminados()->where('id_personal', $personal->id_personal)->pluck('id_bien');

        $personal->delete();

        if ($afectados->isNotEmpty()) {
            Bien::withEliminados()
                ->whereIn('id_bien', $afectados)
                ->whereNull('id_area')
                ->whereNull('id_personal')
                ->where('estatus', 'Asignado')
                ->update(['estatus' => 'Disponible']);
        }

        return redirect()->route('admin.personal')->with('success', 'Personal eliminado correctamente.');
    }

    public function downloadTemplate()
    {
        $headers = ['nombre', 'apellido_paterno', 'apellido_materno', 'puesto', 'correo', 'telefono', 'id_area', 'estatus'];

        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', $headers)."\n";
        $csv .= '"Juan","Pérez","García","Docente","juan.perez@ejemplo.com","555-123-4567","Dirección","Activo"'."\n";

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-personal.csv"',
        ]);
    }

    public function importExcel(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $mapaEncabezados = [
            'nombre' => 'nombre',
            'nombre(s)' => 'nombre',
            'nombres' => 'nombre',
            'apellido paterno' => 'apellido_paterno',
            'apellido_paterno' => 'apellido_paterno',
            'apellido materno' => 'apellido_materno',
            'apellido_materno' => 'apellido_materno',
            'puesto' => 'puesto',
            'cargo' => 'puesto',
            'correo' => 'correo',
            'email' => 'correo',
            'telefono' => 'telefono',
            'teléfono' => 'telefono',
            'id_area' => 'id_area',
            'area' => 'id_area',
            'estatus' => 'estatus',
            'estado' => 'estatus',
        ];

        try {
            [$importados, $actualizados, $errores] = $this->leerArchivoImportacion(
                $request,
                fn (array $data) => $this->importPersonalFromArray($data),
                $mapaEncabezados
            );
        } catch (\Exception $e) {
            return redirect()->route('admin.personal')->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }

        $mensaje = "Se importaron {$importados} registros de personal correctamente.";
        if ($actualizados > 0) {
            $mensaje .= " Se actualizaron {$actualizados} registros existentes.";
        }
        if (! empty($errores)) {
            $mensaje .= ' Se encontraron '.count($errores).' errores.';
            if (count($errores) <= 5) {
                $mensaje .= ' Detalles: '.implode('; ', $errores);
            }
        }

        return redirect()->route('admin.personal')->with('success', $mensaje);
    }

    private function importPersonalFromArray(array $data): string
    {
        $nombre = trim($data['nombre'] ?? '');
        $apellidoPaterno = trim($data['apellido_paterno'] ?? '');
        $apellidoMaterno = trim($data['apellido_materno'] ?? '');

        if ($nombre === '') {
            throw new \Exception('El nombre es requerido.');
        }
        if (mb_strlen($nombre) < 2) {
            throw new \Exception('El nombre "'.$nombre.'" debe tener al menos 2 caracteres.');
        }
        if ($apellidoPaterno === '') {
            throw new \Exception('El apellido paterno es requerido para "'.$nombre.'".');
        }
        if (mb_strlen($apellidoPaterno) < 2) {
            throw new \Exception('El apellido paterno "'.$apellidoPaterno.'" debe tener al menos 2 caracteres.');
        }

        $puesto = trim($data['puesto'] ?? '');
        if ($puesto === '') {
            throw new \Exception('El puesto es requerido para "'.$nombre.'".');
        }
        if (mb_strlen($puesto) < 2) {
            throw new \Exception('El puesto "'.$puesto.'" debe tener al menos 2 caracteres.');
        }

        $correo = trim($data['correo'] ?? '');
        if ($correo !== '' && ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('El correo "'.$correo.'" no es válido.');
        }

        $telefono = trim($data['telefono'] ?? '');
        if ($telefono !== '' && ! preg_match('/^[0-9\+\-\(\)\s]*$/', $telefono)) {
            throw new \Exception('El teléfono "'.$telefono.'" solo puede contener números, guiones, paréntesis y espacios.');
        }

        $estatus = trim($data['estatus'] ?? '');
        if ($estatus === '') {
            $estatus = 'Activo';
        }
        if (! in_array($estatus, ['Activo', 'Inactivo'])) {
            throw new \Exception('El estado "'.$estatus.'" no es válido para "'.$nombre.'". Use Activo o Inactivo.');
        }

        $idArea = $this->resolverArea($data['id_area'] ?? '');

        $personal = $this->encontrarPersonalExistente($nombre, $apellidoPaterno, $apellidoMaterno);

        if ($personal) {
            $personal->update([
                'apellido_materno' => $apellidoMaterno,
                'puesto' => $puesto,
                'correo' => $correo !== '' ? $correo : null,
                'telefono' => $telefono !== '' ? $telefono : null,
                'id_area' => $idArea,
                'estatus' => $estatus,
            ]);

            return 'actualizado';
        }

        Personal::create([
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'puesto' => $puesto,
            'correo' => $correo !== '' ? $correo : null,
            'telefono' => $telefono !== '' ? $telefono : null,
            'id_area' => $idArea,
            'estatus' => $estatus,
            'fecha_registro' => now(),
        ]);

        return 'creado';
    }

    private function resolverArea(string $valor): ?int
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        if (ctype_digit($valor)) {
            $area = Area::find((int) $valor);
            if ($area) {
                return $area->id_area;
            }
            throw new \Exception('El área con ID '.$valor.' no existe.');
        }

        $area = Area::whereRaw('LOWER(nombre_area) = ?', [mb_strtolower($valor)])->first();
        if ($area) {
            return $area->id_area;
        }

        $area = Area::create([
            'nombre_area' => $valor,
            'descripcion' => 'Importada desde Excel',
            'estatus' => 'Activa',
            'fecha_registro' => now(),
        ]);

        return $area->id_area;
    }

    private function encontrarPersonalExistente(string $nombre, string $apellidoPaterno, string $apellidoMaterno): ?Personal
    {
        return Personal::where('nombre', $nombre)
            ->where('apellido_paterno', $apellidoPaterno)
            ->when($apellidoMaterno === '', fn ($query) => $query->where(function ($query) {
                $query->whereNull('apellido_materno')->orWhere('apellido_materno', '');
            }), fn ($query) => $query->where('apellido_materno', $apellidoMaterno))
            ->first();
    }
}
