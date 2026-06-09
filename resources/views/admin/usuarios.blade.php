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
        <div class="component-alert component-alert-success" style="margin-bottom:20px;">
            <div class="component-alert-content">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="component-alert component-alert-error" style="margin-bottom:20px;">
            <div class="component-alert-content">{{ session('error') }}</div>
        </div>
    @endif

    @if($isAdmin)
        <div class="card" style="border-radius:16px;margin-bottom:24px;">
            <div class="component-card-body">
                <h3 style="color:var(--primary-dark);margin-bottom:16px;">
                    <i class="fa-solid fa-user-plus"></i> Registrar nuevo usuario
                </h3>

                <form method="POST" action="{{ route('admin.usuarios.store') }}">
                    @csrf
                    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
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
                        <div class="form-group" style="display:flex;align-items:flex-end;">
                            <button type="submit" class="btn-agregar" style="width:100%;">
                                <i class="fa-solid fa-floppy-disk"></i> Crear usuario
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="component-alert component-alert-warning" style="margin-bottom:24px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div class="component-alert-content">
                Solo los administradores pueden gestionar usuarios.
            </div>
        </div>
    @endif

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo electr&oacute;nico</th>
                    <th>Rol</th>
                    <th>Fecha de registro</th>
                    @if($isAdmin)
                        <th>Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="estado {{ $user->role === 'admin' ? 'disponible' : 'pendiente' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        @if($isAdmin)
                            <td class="acciones">
                                @if($user->id !== Auth::id())
                                    <form method="POST" action="{{ route('admin.usuarios.destroy', $user) }}" style="display:inline;" onsubmit="return confirmAction(event, '&iquest;Eliminar este usuario? Esta acci&oacute;n no se puede deshacer.', 'S&iacute;, eliminar', 'Cancelar', 'error')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Eliminar" class="action-btn action-danger" style="background:none;border:1px solid var(--border);cursor:pointer;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <span style="color:var(--muted);font-size:13px;">Usuario actual</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 5 : 4 }}" style="text-align:center;padding:20px;">No hay usuarios registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="paginacion">
            <span>Mostrando {{ $users->count() }} usuarios</span>
        </div>
    </div>
@endsection
