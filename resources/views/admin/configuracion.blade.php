@extends('layouts.admin')

@section('title', 'Configuracion del Sistema')

@section('content')
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal.show { display: flex; justify-content: center; align-items: center; }
        .modal-content { background-color: var(--surface); padding: 30px; border-radius: 12px; width: 90%; max-width: 640px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        .modal-header h2 { margin: 0; color: var(--primary-dark); font-size: 22px; }
        .modal-header button { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted); }
        .modal-footer { display: flex; gap: 12px; justify-content: flex-end; padding-top: 15px; border-top: 1px solid var(--border); }
        .btn-cancel { background: var(--hover); color: var(--text); }
        .btn-submit { background: var(--primary); color: white; }
        .settings-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
        .setting-card { background: var(--surface); padding: 22px; border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .setting-card h3 { margin-top: 0; color: var(--primary-dark); }
        .setting-card p { color: var(--muted); margin-bottom: 15px; }
        .setting-list { display: grid; gap: 10px; margin-top: 18px; }
        .setting-row { padding: 10px 0; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .setting-row:last-child { border-bottom: 0; }
        .setting-row small { color: var(--muted); }
    </style>

    <div class="header">
        <div>
            <h1>Configuracion del Sistema</h1>
            <p>Administra usuarios, parametros y respaldos</p>
        </div>
    </div>

    @if(session('success'))
        <div class="setting-card" style="margin-bottom: 20px; border-color: var(--success-border); background: var(--success-bg); color: var(--success-text);">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="setting-card" style="margin-bottom: 20px; border-color: var(--danger-border); background: var(--danger-bg); color: var(--danger);">
            {{ session('error') }}
        </div>
    @endif

    @unless(Auth::user()->isAdmin())
        <div class="setting-card" style="margin-bottom: 20px; border-color: var(--warning-border); background: var(--warning-bg); color: var(--warning);">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Acceso restringido</h3>
            <p>La configuracion avanzada no esta disponible para el rol Visualizador.</p>
        </div>
    @endunless

    <div class="settings-container">
        @if(Auth::user()->isAdmin())
            <div class="setting-card">
                <h3><i class="fa-solid fa-users"></i> Gestion de Usuarios</h3>
                <p>Crea y elimina usuarios del sistema.</p>
                <button type="button" class="btn-agregar" onclick="openModalUser()">
                    <i class="fa-solid fa-user-plus"></i>
                    Nuevo usuario
                </button>

                <div class="setting-list">
                    @forelse($users ?? [] as $user)
                        <div class="setting-row">
                            <div>
                                <strong>{{ $user->name }}</strong><br>
                                <small>{{ $user->email }} - {{ ucfirst($user->role) }}</small>
                            </div>
                            @if($user->id !== Auth::id())
                                <form method="POST" action="{{ route('admin.configuracion.destroy', $user) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-delete" aria-label="Eliminar" onclick="return confirm('Seguro que deseas eliminar este usuario?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p>No hay usuarios registrados.</p>
                    @endforelse
                </div>
            </div>

            <div class="setting-card">
                <h3><i class="fa-solid fa-database"></i> Respaldo de Datos</h3>
                <p>Descarga un respaldo JSON con usuarios, areas, personal, bienes, historial y parametros.</p>
                <a href="{{ route('admin.configuracion.backup') }}" class="btn-agregar">
                    <i class="fa-solid fa-download"></i>
                    Descargar respaldo
                </a>

                <form method="POST" action="{{ route('admin.configuracion.restore') }}" enctype="multipart/form-data" style="margin-top: 18px;">
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

            <div class="setting-card">
                <h3><i class="fa-solid fa-sliders"></i> Parametros</h3>
                <p>Configura datos generales de la institucion e inventario.</p>

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
                    <label style="display:flex; gap:10px; align-items:center; margin-bottom:16px;">
                        <input type="checkbox" name="numeracion_automatica" value="1" {{ ($parametros['numeracion_automatica'] ?? '0') === '1' ? 'checked' : '' }}>
                        Numeracion automatica
                    </label>
                    <button type="submit" class="btn-agregar">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar parametros
                    </button>
                </form>
            </div>
        @else
            <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                <p><i class="fa-solid fa-lock"></i> Solo administradores pueden acceder a esta seccion.</p>
            </div>
        @endif

        <div class="setting-card">
            <h3><i class="fa-solid fa-palette"></i> Tema</h3>
            <p>Cambia el tema de la interfaz.</p>
            <select id="theme-select" onchange="changeTheme()" style="width: 100%;">
                <option value="light">Claro</option>
                <option value="dark">Oscuro</option>
            </select>
        </div>
    </div>

    @if(Auth::user()->isAdmin())
        <div id="modalUser" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Crear nuevo usuario</h2>
                    <button type="button" onclick="closeModalUser()">&times;</button>
                </div>
                <form id="formUser" method="POST" action="{{ route('admin.configuracion.store') }}">
                    @csrf
                    <div class="modal-body">
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
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" onclick="closeModalUser()">Cancelar</button>
                        <button type="submit" class="btn-submit">Crear usuario</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        function openModalUser() {
            document.getElementById('modalUser').classList.add('show');
            document.getElementById('formUser').reset();
        }

        function closeModalUser() {
            document.getElementById('modalUser').classList.remove('show');
        }

        function changeTheme() {
            var select = document.getElementById('theme-select');
            if (!select) return;

            var theme = select.value === 'dark' ? 'dark' : 'light';
            document.documentElement.dataset.theme = theme;
            localStorage.setItem('inventario-theme', theme);
        }

        document.addEventListener('DOMContentLoaded', function () {
            var select = document.getElementById('theme-select');
            if (!select) return;

            var savedTheme = localStorage.getItem('inventario-theme');
            select.value = savedTheme === 'dark' || savedTheme === 'light'
                ? savedTheme
                : (document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');
        });

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modalUser');
            if (modal && event.target === modal) {
                closeModalUser();
            }
        });
    </script>
@endsection
