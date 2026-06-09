@extends('layouts.admin')

@section('title', 'Configuracion del Sistema')

@section('content')
    <div class="header">
        <div>
            <h1>Configuracion del Sistema</h1>
            <p>Administra usuarios, parametros y respaldos</p>
        </div>
    </div>

    @if(session('success'))
        <div class="component-alert component-alert-success" style="margin-bottom:20px;">
            <div class="component-alert-content">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="component-alert component-alert-error" style="margin-bottom:20px;">
            <div class="component-alert-content">{{ session('error') }}</div>
        </div>
    @endif

    @unless(Auth::user()->isAdmin())
        <div class="component-alert component-alert-warning" style="margin-bottom:20px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div class="component-alert-content">La configuracion avanzada no esta disponible para el rol Visualizador.</div>
        </div>
    @endunless

    <div class="settings-container" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;">
        @if(Auth::user()->isAdmin())
            <div class="card" style="border-radius:16px;">
                <div class="component-card-body">
                    <h3 style="color:var(--primary-dark);margin-bottom:12px;"><i class="fa-solid fa-users"></i> Gestion de Usuarios</h3>
                    <p style="color:var(--muted);margin-bottom:15px;">Crea y elimina usuarios del sistema.</p>
                    <button type="button" class="btn-agregar" onclick="openModal('modalUser')">
                        <i class="fa-solid fa-user-plus"></i>
                        Nuevo usuario
                    </button>

                    <div style="display:grid;gap:10px;margin-top:18px;">
                        @forelse($users ?? [] as $user)
                            <div style="padding:10px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;gap:12px;align-items:center;">
                                <div>
                                    <strong>{{ $user->name }}</strong><br>
                                    <small style="color:var(--muted);">{{ $user->email }} - {{ ucfirst($user->role) }}</small>
                                </div>
                                @if($user->id !== Auth::id())
                                    <form method="POST" action="{{ route('admin.configuracion.destroy', $user) }}" style="display:inline;" onsubmit="return confirmAction(event, 'Seguro que deseas eliminar este usuario?', 'Sí, eliminar', 'Cancelar', 'error')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-delete" aria-label="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p style="color:var(--muted);">No hay usuarios registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card" style="border-radius:16px;">
                <div class="component-card-body">
                    <h3 style="color:var(--primary-dark);margin-bottom:12px;"><i class="fa-solid fa-database"></i> Respaldo de Datos</h3>
                    <p style="color:var(--muted);margin-bottom:15px;">Descarga un respaldo JSON con usuarios, areas, personal, bienes, historial y parametros.</p>
                    <a href="{{ route('admin.configuracion.backup') }}" class="btn-agregar">
                        <i class="fa-solid fa-download"></i>
                        Descargar respaldo
                    </a>

                    <form method="POST" action="{{ route('admin.configuracion.restore') }}" enctype="multipart/form-data" style="margin-top:18px;">
                        @csrf
                        <div class="form-group">
                            <label for="respaldo">Validar respaldo</label>
                            <input type="file" id="respaldo" name="respaldo" accept=".json,.txt">
                        </div>
                        <button type="submit" class="btn-secundario">
                            <i class="fa-solid fa-upload"></i>
                            Validar archivo
                        </button>
                    </form>
                </div>
            </div>

            <div class="card" style="border-radius:16px;">
                <div class="component-card-body">
                    <h3 style="color:var(--primary-dark);margin-bottom:12px;"><i class="fa-solid fa-sliders"></i> Parametros</h3>
                    <p style="color:var(--muted);margin-bottom:15px;">Configura datos generales de la institucion e inventario.</p>

                    <form method="POST" action="{{ route('admin.configuracion.parametros') }}">
                        @csrf
                        <div class="form-group">
                            <label for="institucion_nombre">Nombre de la institucion</label>
                            <input type="text" id="institucion_nombre" name="institucion_nombre" value="{{ $parametros['institucion_nombre'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label for="institucion_contacto">Datos de contacto</label>
                            <input type="text" id="institucion_contacto" name="institucion_contacto" value="{{ $parametros['institucion_contacto'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label for="inventario_prefijo">Prefijo de inventario</label>
                            <input type="text" id="inventario_prefijo" name="inventario_prefijo" value="{{ $parametros['inventario_prefijo'] ?? '' }}">
                        </div>
                        <label style="display:flex;gap:10px;align-items:center;margin-bottom:16px;">
                            <input type="checkbox" name="numeracion_automatica" value="1" {{ ($parametros['numeracion_automatica'] ?? '0') === '1' ? 'checked' : '' }}>
                            Numeracion automatica
                        </label>
                        <button type="submit" class="btn-agregar">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar parametros
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div style="grid-column:1/-1;text-align:center;padding:40px;">
                <p style="color:var(--muted);"><i class="fa-solid fa-lock"></i> Solo administradores pueden acceder a esta seccion.</p>
            </div>
        @endif

        <div class="card" style="border-radius:16px;">
            <div class="component-card-body">
                <h3 style="color:var(--primary-dark);margin-bottom:12px;"><i class="fa-solid fa-palette"></i> Tema</h3>
                <p style="color:var(--muted);margin-bottom:15px;">Cambia el tema de la interfaz.</p>
                <div class="theme-setting">
                    <div>
                        <strong>Modo oscuro</strong>
                        <span>Alterna entre tema claro y oscuro</span>
                    </div>
                    <label class="theme-switch">
                        <input type="checkbox" data-theme-toggle>
                        <span class="theme-slider">
                            <i class="fa-solid fa-sun"></i>
                            <i class="fa-solid fa-moon"></i>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->isAdmin())
        <div id="modalUser" class="component-modal">
            <div class="component-modal-content component-modal-md">
                <div class="component-modal-header">
                    <h2>Crear nuevo usuario</h2>
                    <button type="button" class="component-modal-close" onclick="closeModal('modalUser')">&times;</button>
                </div>
                <form id="formUser" method="POST" action="{{ route('admin.configuracion.store') }}">
                    @csrf
                    <div class="component-modal-body">
                        <div class="form-group">
                            <label for="name">Nombre completo *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Correo electronico *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Contrasena *</label>
                            <input type="password" id="password" name="password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirmar contrasena *</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="role">Rol *</label>
                            <select id="role" name="role" required>
                                <option value="visualizador">Visualizador</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="component-modal-footer">
                        <button type="button" class="btn-secundario" onclick="closeModal('modalUser')">Cancelar</button>
                        <button type="submit" class="btn-agregar">Crear usuario</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        function changeTheme() {
            var checkbox = document.querySelector('[data-theme-toggle]');
            if (!checkbox) return;
            var theme = checkbox.checked ? 'dark' : 'light';
            document.documentElement.dataset.theme = theme;
            localStorage.setItem('inventario-theme', theme);
        }

        document.addEventListener('DOMContentLoaded', function () {
            var checkbox = document.querySelector('[data-theme-toggle]');
            if (!checkbox) return;
            checkbox.addEventListener('change', changeTheme);
            var savedTheme = localStorage.getItem('inventario-theme');
            if (savedTheme) {
                checkbox.checked = savedTheme === 'dark';
                document.documentElement.dataset.theme = savedTheme;
            }
        });
    </script>
@endsection
