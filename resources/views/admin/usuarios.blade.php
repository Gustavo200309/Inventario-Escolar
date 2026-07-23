@extends('layouts.admin')

@section('title', 'Gestion de Usuarios')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de Usuarios</h1>
            <p>Administra los usuarios del sistema</p>
        </div>
    </div>

    @if(session('success'))
        <div class="setting-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="setting-alert error">
            {{ session('error') }}
        </div>
    @endif

    @unless(Auth::user()->isAdmin())
        <div class="setting-alert warning">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Acceso restringido</h3>
            <p>La gesti&oacute;n de usuarios no est&aacute; disponible para el rol Visualizador.</p>
        </div>
    @endunless

    @if(Auth::user()->isAdmin())
        <div class="setting-card" style="margin-bottom:24px;">
            <h3><i class="fa-solid fa-user-plus"></i> Agregar Usuario</h3>
            <form method="POST" action="{{ route('admin.usuarios.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
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
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirmar contrase&ntilde;a *</label>
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
                <button type="submit" class="btn-agregar" style="margin-top:16px;">
                    <i class="fa-solid fa-user-plus"></i> Agregar Usuario
                </button>
            </form>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo electr&oacute;nico</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha de creaci&oacute;n</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users ?? [] as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ucfirst($user->role) }}</td>
                            <td><span class="estado activo">Activo</span></td>
                            <td>{{ $user->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                            <td class="acciones">
                                @if($user->id !== Auth::id())
                                    <form method="POST" action="{{ route('admin.usuarios.destroy', $user) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="action-btn action-danger" aria-label="Eliminar" onclick="confirmThenSubmit(this, '¿Seguro que deseas eliminar este usuario?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <span style="color:var(--muted);font-size:13px;">Usuario actual</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:20px;">No hay usuarios registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="paginacion">
                <span>Mostrando {{ count($users ?? []) }} usuarios</span>
            </div>
        </div>
    @endif
@endsection