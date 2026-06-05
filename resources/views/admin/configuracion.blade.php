@extends('layouts.admin')

@section('title', 'Configuracion del Sistema')

@section('content')
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal.show { display: flex; justify-content: center; align-items: center; }
        .modal-content { background-color: #f7f8f6; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e8ede4; padding-bottom: 15px; }
        .modal-header h2 { margin: 0; color: #2f3e34; font-size: 22px; }
        .modal-header button { background: none; border: none; font-size: 24px; cursor: pointer; color: #6d746b; }
        .modal-body { margin-bottom: 20px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: #245c2d; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #d8ddd4; border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #2f943c; box-shadow: 0 0 0 3px rgba(47,148,60,0.1); }
        .modal-footer { display: flex; gap: 12px; justify-content: flex-end; padding-top: 15px; border-top: 1px solid #e8ede4; }
        .modal-footer button { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .btn-cancel { background: #e8ede4; color: #2f3e34; }
        .btn-cancel:hover { background: #dae2d6; }
        .btn-submit { background: #2f943c; color: white; }
        .btn-submit:hover { background: #21692c; transform: translateY(-2px); }
        .settings-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .setting-card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e8ede4; }
        .setting-card h3 { margin-top: 0; color: #2f3e34; }
        .setting-card p { color: #6d746b; margin-bottom: 15px; }
    </style>

    <div class="header">
        <div>
            <h1>Configuraci&oacute;n del Sistema</h1>
            <p>Administra usuarios y configuraciones</p>
        </div>
    </div>

    @unless(Auth::user()->isAdmin())
        <div class="setting-card" style="grid-column: 1/-1; background: #fff4e5; border-color: #facc15; color: #92400e;">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Acceso restringido</h3>
            <p>La configuración avanzada no está disponible para el rol Visualizador.</p>
        </div>
    @endunless

    <div class="settings-container">
        @if(Auth::user()->isAdmin())
            <!-- Card: Gestión de Usuarios -->
            <div class="setting-card">
                <h3><i class="fa-solid fa-users"></i> Gesti&oacute;n de Usuarios</h3>
                <p>Crea, edita y elimina usuarios del sistema</p>
                <button type="button" class="btn-agregar" onclick="openModalUser()">
                    <i class="fa-solid fa-user-plus"></i>
                    Nuevo usuario
                </button>
                
                <div style="margin-top: 20px;">
                    <h4 style="color: #245c2d; margin-bottom: 10px;">Usuarios registrados:</h4>
                    <div style="max-height: 200px; overflow-y: auto;">
                        @forelse($users ?? [] as $user)
                            <div style="padding: 8px; border-bottom: 1px solid #e8ede4; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <p style="margin: 0; font-weight: 600;">{{ $user->name }}</p>
                                    <p style="margin: 0; font-size: 12px; color: #6d746b;">{{ $user->email }} - {{ ucfirst($user->role) }}</p>
                                </div>
                                @if($user->id !== Auth::id())
                                    <form method="POST" action="{{ route('admin.configuracion.destroy', $user) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-delete" onclick="return confirm('¿Está seguro?')"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p style="color: #6d746b;">No hay usuarios</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Card: Respaldo y Exportación -->
            <div class="setting-card">
                <h3><i class="fa-solid fa-database"></i> Respaldo de Datos</h3>
                <p>Exporta los datos del sistema</p>
                <a href="#" class="btn-agregar" style="text-decoration: none;">
                    <i class="fa-solid fa-download"></i>
                    Descargar respaldo
                </a>
            </div>

            <!-- Card: Parámetros del Sistema -->
            <div class="setting-card">
                <h3><i class="fa-solid fa-sliders"></i> Par&aacute;metros</h3>
                <p>Configura parámetros generales del sistema</p>
                <button type="button" class="btn-agregar" onclick="alert('Por implementar')">
                    <i class="fa-solid fa-gear"></i>
                    Configurar
                </button>
            </div>
        @else
            <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                <p><i class="fa-solid fa-lock"></i> Solo administradores pueden acceder a esta sección</p>
            </div>
        @endif

        <!-- Card: Tema (disponible para todos) -->
        <div class="setting-card">
            <h3><i class="fa-solid fa-palette"></i> Tema</h3>
            <p>Cambia el tema de la interfaz</p>
            <select id="theme-select" onchange="changeTheme()" style="width: 100%; padding: 8px; border: 1px solid #d8ddd4; border-radius: 8px;">
                <option value="light">Claro</option>
                <option value="dark">Oscuro</option>
            </select>
        </div>
    </div>

    @if(Auth::user()->isAdmin())
        <!-- Modal Crear Usuario -->
        <div id="modalUser" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Crear nuevo usuario</h2>
                    <button onclick="closeModalUser()">&times;</button>
                </div>
                <form id="formUser" method="POST" action="{{ route('admin.configuracion.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Nombre completo *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Correo electr&oacute;nico *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Contrase&ntilde;a *</label>
                            <input type="password" id="password" name="password" required minlength="8">
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirmar contrase&ntilde;a *</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
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
            var currentTheme = savedTheme === 'dark' || savedTheme === 'light'
                ? savedTheme
                : (document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');

            select.value = currentTheme;
        });

        window.onclick = function(event) {
            const modal = document.getElementById('modalUser');
            if (modal && event.target === modal) {
                closeModalUser();
            }
        }
    </script>
@endsection
