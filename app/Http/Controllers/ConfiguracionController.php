<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use App\Models\HistorialAsignacion;
use App\Models\ParametroSistema;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $usuarios = [];
        $parametros = collect();

        if ($user && $user->isAdmin()) {
            $usuarios = User::orderBy('created_at', 'desc')->get();
            $parametros = ParametroSistema::pluck('valor', 'clave');
        }

        return view('admin.configuracion', [
            'users' => $usuarios,
            'parametros' => $parametros,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin('Solo administradores pueden crear usuarios.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:admin,visualizador'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return Redirect::route('admin.configuracion')->with('success', 'Usuario creado correctamente.');
    }

    public function updateParametros(Request $request)
    {
        $this->authorizeAdmin('Solo administradores pueden actualizar parametros.');

        $data = $request->validate([
            'institucion_nombre' => ['nullable', 'string', 'max:255'],
            'institucion_contacto' => ['nullable', 'string', 'max:255'],
            'inventario_prefijo' => ['nullable', 'string', 'max:20'],
            'numeracion_automatica' => ['nullable', 'boolean'],
        ]);

        $data['numeracion_automatica'] = $request->boolean('numeracion_automatica') ? '1' : '0';

        foreach ($data as $clave => $valor) {
            ParametroSistema::updateOrCreate(
                ['clave' => $clave],
                ['valor' => $valor]
            );
        }

        return Redirect::route('admin.configuracion')->with('success', 'Parametros actualizados correctamente.');
    }

    public function backup()
    {
        $this->authorizeAdmin('Solo administradores pueden descargar respaldos.');

        $payload = [
            'generado_en' => now()->toDateTimeString(),
            'version' => '1.0',
            'tablas' => [
                'users' => User::select('id', 'name', 'email', 'role', 'created_at', 'updated_at')->get(),
                'areas' => Area::orderBy('id_area')->get(),
                'personal' => Personal::orderBy('id_personal')->get(),
                'bienes' => Bien::with(['area', 'personal'])->orderBy('id_bien')->get(),
                'historial_asignaciones' => HistorialAsignacion::orderBy('id_historial')->get(),
                'parametros_sistema' => ParametroSistema::orderBy('clave')->get(),
            ],
        ];

        return Response::make(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="respaldo-inventario-' . now()->format('Ymd-His') . '.json"',
        ]);
    }

    public function restore(Request $request)
    {
        $this->authorizeAdmin('Solo administradores pueden restaurar respaldos.');

        $data = $request->validate([
            'respaldo' => ['required', 'file', 'mimes:json,txt'],
        ]);

        $content = file_get_contents($data['respaldo']->getRealPath());
        $payload = json_decode($content, true);

        if (! is_array($payload) || ! isset($payload['tablas'])) {
            return Redirect::route('admin.configuracion')->with('error', 'El archivo de respaldo no tiene un formato valido.');
        }

        return Redirect::route('admin.configuracion')->with('success', 'Respaldo validado correctamente. La restauracion destructiva debe ejecutarse manualmente por seguridad.');
    }

    public function destroy(User $usuario)
    {
        $this->authorizeAdmin('Solo administradores pueden eliminar usuarios.');

        if (Auth::id() === $usuario->id) {
            return Redirect::route('admin.configuracion')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $usuario->delete();

        return Redirect::route('admin.configuracion')->with('success', 'Usuario eliminado correctamente.');
    }

    private function authorizeAdmin(string $message): void
    {
        $user = Auth::user();

        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            abort(403, $message);
        }
    }
}
