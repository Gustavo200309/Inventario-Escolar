@extends('layouts.admin')

@section('title', 'Gestion de Personal')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de Personal</h1>
            <p>Administra el personal y sus asignaciones</p>
        </div>

        <div class="header-right">
            @include('admin.partials.header-logos')

            @if(Auth::user()->isAdmin())
                <div class="page-actions">
                    <button type="button" class="btn-agregar" onclick="openModal('modalImportar')">
                        <i class="fa-solid fa-file-import"></i> Importar
                    </button>
                    <button type="button" class="btn-agregar" onclick="openModalPersonal()">
                        <i class="fa-solid fa-plus"></i>
                        Agregar personal
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="component-alert component-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span class="component-alert-content">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="component-alert component-alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span class="component-alert-content">{{ session('error') }}</span>
        </div>
    @endif


    <div class="buscador">
        <form method="GET" class="buscar-form" style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;width:100%;">
            <div class="input-buscar" style="flex:1;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por nombre, cargo o &aacute;rea..." value="{{ $search ?? '' }}">
            </div>
            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
            @if($search)
                <a href="{{ route('admin.personal') }}" class="btn-secundario"><i class="fa-solid fa-times"></i> Limpiar</a>
            @endif
        </form>
    </div>

    <div class="personal-grid">
        @forelse($personals as $personal)
            <article class="card">
                <div class="card-top">
                    <div class="avatar">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span class="estado {{ strtolower($personal->estatus) }}">{{ $personal->estatus }}</span>
                </div>

                <h2 class="nombre">{{ $personal->nombre }} {{ $personal->apellido_paterno }} {{ $personal->apellido_materno }}</h2>
                <p class="puesto">{{ $personal->puesto }}</p>
                <p class="area">{{ $personal->area?->nombre_area ?? 'Sin área' }}</p>

                <div class="linea"></div>

                <div class="datos">
                    <div class="dato">
                        <span class="label">Correo</span>
                        <span class="valor">{{ $personal->correo ?? 'N/A' }}</span>
                    </div>
                    <div class="dato">
                        <span class="label">Tel&eacute;fono</span>
                        <span class="valor">{{ $personal->telefono ?? 'N/A' }}</span>
                    </div>
                    <div class="dato">
                        <span class="label">Bienes asignados</span>
                        <span class="valor">{{ $personal->bienes_count }}</span>
                    </div>
                </div>

                <div class="botones">
                    <button type="button" class="btn-ver" onclick="openDetailsPersonal(this)"
                        data-id_personal="{{ $personal->id_personal }}"
                        data-nombre="{{ $personal->nombre }} {{ $personal->apellido_paterno }} {{ $personal->apellido_materno }}"
                        data-puesto="{{ $personal->puesto }}"
                        data-correo="{{ $personal->correo }}"
                        data-telefono="{{ $personal->telefono }}"
                        data-area_nombre="{{ $personal->area?->nombre_area ?? 'Sin área' }}"
                        data-estatus="{{ $personal->estatus }}"
                        data-bienes_count="{{ $personal->bienes_count }}"
                    >Ver perfil</button>
                    @if(Auth::user()->isAdmin())
                        <button type="button" class="btn-icon action-edit"
                            data-id_personal="{{ $personal->id_personal }}"
                            data-nombre="{{ $personal->nombre }}"
                            data-apellido_paterno="{{ $personal->apellido_paterno }}"
                            data-apellido_materno="{{ $personal->apellido_materno }}"
                            data-puesto="{{ $personal->puesto }}"
                            data-correo="{{ $personal->correo }}"
                            data-telefono="{{ $personal->telefono }}"
                            data-id_area="{{ $personal->id_area }}"
                            data-estatus="{{ $personal->estatus }}"
                            onclick="editPersonal(this)"
                            aria-label="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" action="{{ route('admin.personal.destroy', $personal) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn-icon btn-delete action-danger" aria-label="Eliminar" onclick="confirmThenSubmit(this, '¿Eliminar este personal?')"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p style="grid-column: 1/-1; text-align: center; padding: 40px;">No hay personal registrado</p>
        @endforelse
    </div>

    <!-- Modal Agregar/Editar Personal -->
    <div id="modalPersonal" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2 id="modalPersonalTitle">Agregar personal</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalPersonal')">&times;</button>
            </div>
            <form id="formPersonal" method="POST" action="{{ old('personal_edit_id') ? url('/personal/' . old('personal_edit_id')) : route('admin.personal.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalPersonalMethod" value="{{ old('personal_edit_id') ? 'PUT' : 'POST' }}">
                <input type="hidden" name="personal_edit_id" id="personal_edit_id" value="{{ old('personal_edit_id') }}">
                <div class="component-modal-body">
                    @if($errors->any())
                        <div class="component-alert component-alert-error" style="margin-bottom:15px;">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span class="component-alert-content">
                                <ul style="margin:0;padding-left:18px;">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </span>
                        </div>
                    @endif
                    <div class="grid">
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" required minlength="2" maxlength="100" value="{{ old('nombre') }}">
                        </div>
                        <div class="form-group">
                            <label for="apellido_paterno">Apellido Paterno *</label>
                            <input type="text" id="apellido_paterno" name="apellido_paterno" required minlength="2" maxlength="100" value="{{ old('apellido_paterno') }}">
                        </div>
                        <div class="form-group">
                            <label for="apellido_materno">Apellido Materno</label>
                            <input type="text" id="apellido_materno" name="apellido_materno" maxlength="100" value="{{ old('apellido_materno') }}">
                        </div>
                        <div class="form-group">
                            <label for="puesto">Puesto *</label>
                            <input type="text" id="puesto" name="puesto" required minlength="2" maxlength="100" value="{{ old('puesto') }}">
                        </div>
                        <div class="form-group">
                            <label for="correo">Correo</label>
                            <input type="email" id="correo" name="correo" maxlength="150" value="{{ old('correo') }}">
                        </div>
                        <div class="form-group">
                            <label for="telefono">Tel&eacute;fono</label>
                            <input type="tel" id="telefono" name="telefono" maxlength="20" pattern="[0-9\+\-\(\)\s]*" placeholder="Ej: 555-123-4567" value="{{ old('telefono') }}">
                            <small class="field-hint" style="color:var(--muted);font-size:12px;margin-top:4px;display:block;">Solo n&uacute;meros, guiones, par&eacute;ntesis y espacios.</small>
                        </div>
                        <div class="form-group">
                            <label for="id_area">Área</label>
                            <select id="id_area" name="id_area">
                                <option value="">Seleccionar área</option>
                                @foreach($areas ?? [] as $area)
                                    <option value="{{ $area->id_area }}" {{ old('id_area') == $area->id_area ? 'selected' : '' }}>{{ $area->nombre_area }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="estatus">Estado *</label>
                            <select id="estatus" name="estatus" required>
                                <option value="Activo" {{ old('estatus') === 'Activo' ? 'selected' : '' }}>Activo</option>
                                <option value="Inactivo" {{ old('estatus') === 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="component-modal-footer">
                    <button type="button" class="btn-secundario" onclick="closeModal('modalPersonal')">Cancelar</button>
                    <button type="submit" class="btn-agregar">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Perfil Personal -->
    <div id="modalPersonalDetails" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2>Perfil del personal</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalPersonalDetails')">&times;</button>
            </div>
            <div class="component-modal-body">
                <div class="detail-grid">
                    <div class="detail-item" style="grid-column:1/-1;">
                        <span class="detail-label">Nombre completo</span>
                        <span class="detail-value" id="detail_personal_nombre"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Puesto</span>
                        <span class="detail-value" id="detail_personal_puesto"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Área</span>
                        <span class="detail-value" id="detail_personal_area"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Correo</span>
                        <span class="detail-value" id="detail_personal_correo"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Teléfono</span>
                        <span class="detail-value" id="detail_personal_telefono"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value" id="detail_personal_estatus"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Bienes asignados</span>
                        <span class="detail-value" id="detail_personal_bienes"></span>
                    </div>
                </div>
            </div>
            <div class="component-modal-footer">
                <button type="button" class="btn-secundario" onclick="closeModal('modalPersonalDetails')">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal Importar Personal -->
    <div id="modalImportar" class="component-modal">
        <div class="component-modal-content component-modal-sm">
            <div class="component-modal-header">
                <h2>Importar personal</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalImportar')">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.personal.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="component-modal-body">
                    <p style="color:var(--muted);margin-bottom:16px;line-height:1.5;">
                        Sube un archivo Excel (.xlsx, .xls) o CSV con los datos del personal.
                        <a href="{{ route('admin.personal.template') }}" style="color:var(--primary);font-weight:600;">Descargar plantilla</a>
                    </p>
                    <div class="form-group">
                        <label for="archivo">Archivo *</label>
                        <input type="file" id="archivo" name="archivo" accept=".csv,.xlsx,.xls,.txt" required>
                    </div>
                    <div class="form-group">
                        <label>Formato esperado</label>
                        <div style="font-size:13px;color:var(--muted);background:var(--surface-strong);padding:12px;border-radius:10px;border:1px solid var(--border);line-height:1.6;">
                            <strong style="color:var(--text);">Columnas:</strong> nombre, apellido_paterno, apellido_materno, puesto, correo, telefono, id_area, estatus<br>
                            <span style="font-size:12px;">* nombre, apellido_paterno y puesto son obligatorios<br>* id_area se busca por nombre o por ID</span>
                        </div>
                    </div>
                </div>
                <div class="component-modal-footer">
                    <button type="button" class="btn-secundario" onclick="closeModal('modalImportar')">Cancelar</button>
                    <button type="submit" class="btn-agregar"><i class="fa-solid fa-upload"></i> Importar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const personalStoreUrl = "{{ route('admin.personal.store') }}";
        const personalBaseUrl = "{{ url('/personal') }}";

        document.getElementById('formPersonal').addEventListener('submit', function (e) {
            var nombre = document.getElementById('nombre').value.trim();
            var apellido = document.getElementById('apellido_paterno').value.trim();
            var puesto = document.getElementById('puesto').value.trim();
            var correo = document.getElementById('correo').value.trim();
            var telefono = document.getElementById('telefono').value.trim();
            if (nombre.length < 2) {
                e.preventDefault();
                showAlert('El nombre debe tener al menos 2 caracteres.');
                return;
            }
            if (apellido.length < 2) {
                e.preventDefault();
                showAlert('El apellido paterno debe tener al menos 2 caracteres.');
                return;
            }
            if (puesto.length < 2) {
                e.preventDefault();
                showAlert('El puesto debe tener al menos 2 caracteres.');
                return;
            }
            if (correo && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
                e.preventDefault();
                showAlert('El correo electr\u00f3nico no es v\u00e1lido.');
                return;
            }
            if (telefono && !/^[0-9\+\-\(\)\s]+$/.test(telefono)) {
                e.preventDefault();
                showAlert('El tel\u00e9fono solo puede contener n\u00fameros, guiones, par\u00e9ntesis y espacios.');
                return;
            }
        });

        function openModalPersonal() {
            document.getElementById('formPersonal').reset();
            document.getElementById('modalPersonalTitle').textContent = 'Agregar personal';
            document.getElementById('formPersonal').action = personalStoreUrl;
            document.getElementById('modalPersonalMethod').value = 'POST';
            document.getElementById('personal_edit_id').value = '';
            document.querySelector('#modalPersonal .btn-agregar').textContent = 'Guardar';
            openModal('modalPersonal');
        }

        function editPersonal(button) {
            openModal('modalPersonal');
            document.getElementById('modalPersonalTitle').textContent = 'Editar personal';
            document.getElementById('nombre').value = button.dataset.nombre || '';
            document.getElementById('apellido_paterno').value = button.dataset.apellido_paterno || '';
            document.getElementById('apellido_materno').value = button.dataset.apellido_materno || '';
            document.getElementById('puesto').value = button.dataset.puesto || '';
            document.getElementById('correo').value = button.dataset.correo || '';
            document.getElementById('telefono').value = button.dataset.telefono || '';
            document.getElementById('id_area').value = button.dataset.id_area || '';
            document.getElementById('estatus').value = button.dataset.estatus || 'Activo';
            document.getElementById('formPersonal').action = personalBaseUrl + '/' + button.dataset.id_personal;
            document.getElementById('modalPersonalMethod').value = 'PUT';
            document.getElementById('personal_edit_id').value = button.dataset.id_personal || '';
            document.querySelector('#modalPersonal .btn-agregar').textContent = 'Guardar cambios';
        }

        function openDetailsPersonal(button) {
            document.getElementById('detail_personal_nombre').textContent = button.dataset.nombre || 'N/A';
            document.getElementById('detail_personal_puesto').textContent = button.dataset.puesto || 'N/A';
            document.getElementById('detail_personal_area').textContent = button.dataset.area_nombre || 'Sin área';
            document.getElementById('detail_personal_correo').textContent = button.dataset.correo || 'N/A';
            document.getElementById('detail_personal_telefono').textContent = button.dataset.telefono || 'N/A';
            document.getElementById('detail_personal_estatus').textContent = button.dataset.estatus || 'N/A';
            document.getElementById('detail_personal_bienes').textContent = button.dataset.bienes_count || '0';
            openModal('modalPersonalDetails');
        }
        document.addEventListener('DOMContentLoaded', function () {
            if (@json($errors->any())) {
                if (document.getElementById('personal_edit_id').value) {
                    document.getElementById('modalPersonalTitle').textContent = 'Editar personal';
                    document.querySelector('#modalPersonal .btn-agregar').textContent = 'Guardar cambios';
                }
                openModal('modalPersonal');
            }
        });
    </script>

    <style>
        .personal-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media (max-width: 1200px) {
            .personal-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .personal-grid {
                grid-template-columns: 1fr;
            }
        }
        .personal-grid .card {
            padding: 14px;
            border-radius: 14px;
        }
        .personal-grid .card-top {
            margin-bottom: 12px;
            gap: 10px;
        }
        .personal-grid .avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 16px;
        }
        .personal-grid .nombre {
            font-size: 16px;
            margin-bottom: 4px;
        }
        .personal-grid .puesto,
        .personal-grid .area {
            font-size: 13px;
        }
        .personal-grid .linea {
            margin: 10px 0;
        }
        .personal-grid .datos {
            gap: 6px;
            margin-bottom: 10px;
        }
        .personal-grid .dato {
            font-size: 12px;
            gap: 8px;
        }
        .personal-grid .botones {
            gap: 8px;
        }
        .personal-grid .btn-ver {
            min-height: 32px;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 8px;
        }
    </style>
@endsection
