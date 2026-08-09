@extends('layouts.admin')

@section('title', 'Gestion de Areas')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de &Aacute;reas</h1>
            <p>Administra las &aacute;reas institucionales</p>
        </div>

        <div class="header-right">
            @include('admin.partials.header-logos')

            @if(Auth::user()->isAdmin())
                <div class="page-actions">
                    <button type="button" class="btn-agregar" onclick="openModal('modalImportar')">
                        <i class="fa-solid fa-file-import"></i> Importar
                    </button>
                    <button type="button" class="btn-agregar" onclick="openModalArea()">
                        <i class="fa-solid fa-plus"></i>
                        Agregar &aacute;rea
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
                <input type="text" name="search" placeholder="Buscar por nombre de &aacute;rea..." value="{{ $search ?? '' }}">
            </div>
            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
            @if($search)
                <a href="{{ route('admin.areas') }}" class="btn-secundario"><i class="fa-solid fa-times"></i> Limpiar</a>
            @endif
        </form>
    </div>

    <div class="cards">
        @forelse($areas as $area)
            <article class="card">
                <div class="card-top">
                    <div class="area-icon">
                        <i class="fa-solid fa-building"></i>
                    </div>

                    @if(Auth::user()->isAdmin())
                        <div class="card-actions">
                            <button type="button" class="action-btn action-edit"
                                onclick="editArea(this)"
                                data-id_area="{{ $area->id_area }}"
                                data-nombre_area="{{ $area->nombre_area }}"
                                data-descripcion="{{ $area->descripcion }}"
                                data-estatus="{{ $area->estatus }}"
                                aria-label="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.areas.destroy', $area) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="action-btn action-danger" aria-label="Eliminar"
                                    data-bienes_count="{{ $area->bienes_count }}"
                                    data-personal_count="{{ $area->personal_count }}"
                                    onclick="confirmDeleteArea(this)"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    @endif
                </div>

                <h3>{{ $area->nombre_area }}</h3>
                <p class="responsable">{{ $area->descripcion ?? 'Sin descripción' }}</p>

                <div class="linea"></div>

                <div class="datos">
                    <div class="info-item">
                        <span>Bienes asignados</span>
                        <strong>{{ $area->bienes_count }}</strong>
                    </div>
                    <div class="info-item">
                        <span>Personal</span>
                        <strong>{{ $area->personal_count }}</strong>
                    </div>
                </div>

                <button type="button" class="details-btn" onclick="openDetailsArea(this)"
                    data-nombre_area="{{ $area->nombre_area }}"
                    data-descripcion="{{ $area->descripcion }}"
                    data-estatus="{{ $area->estatus }}"
                    data-bienes_count="{{ $area->bienes_count }}"
                    data-personal_count="{{ $area->personal_count }}"
                >Ver detalles</button>
            </article>
        @empty
            <p style="grid-column: 1/-1; text-align: center; padding: 40px;">No hay áreas registradas</p>
        @endforelse
    </div>

    <!-- Modal Agregar/Editar Área -->
    <div id="modalArea" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2 id="modalAreaTitle">Agregar área</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalArea')">&times;</button>
            </div>
            <form id="formArea" method="POST" action="{{ old('area_edit_id') ? url('/areas/' . old('area_edit_id')) : route('admin.areas.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalAreaMethod" value="{{ old('area_edit_id') ? 'PUT' : 'POST' }}">
                <input type="hidden" name="area_edit_id" id="area_edit_id" value="{{ old('area_edit_id') }}">
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
                    <div class="form-group">
                        <label for="nombre_area">Nombre del &aacute;rea *</label>
                        <input type="text" id="nombre_area" name="nombre_area" required minlength="2" maxlength="150" value="{{ old('nombre_area') }}">
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripci&oacute;n</label>
                        <textarea id="descripcion" name="descripcion" rows="4" maxlength="500">{{ old('descripcion') }}</textarea>
                        <small class="field-hint" style="color:var(--muted);font-size:12px;margin-top:4px;display:block;">Opcional. M&aacute;ximo 500 caracteres.</small>
                    </div>
                    <div class="form-group">
                        <label for="estatus">Estado *</label>
                        <select id="estatus" name="estatus" required>
                            <option value="Activa" {{ old('estatus') === 'Activa' ? 'selected' : '' }}>Activa</option>
                            <option value="Inactiva" {{ old('estatus') === 'Inactiva' ? 'selected' : '' }}>Inactiva</option>
                        </select>
                    </div>
                </div>
                <div class="component-modal-footer">
                    <button type="button" class="btn-secundario" onclick="closeModal('modalArea')">Cancelar</button>
                    <button type="submit" class="btn-agregar">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Detalles Área -->
    <div id="modalAreaDetails" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2>Detalles del área</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalAreaDetails')">&times;</button>
            </div>
            <div class="component-modal-body">
                <div class="detail-grid">
                    <div class="detail-item" style="grid-column:1/-1;">
                        <span class="detail-label">Nombre del área</span>
                        <span class="detail-value" id="detail_nombre_area"></span>
                    </div>
                    <div class="detail-item" style="grid-column:1/-1;">
                        <span class="detail-label">Descripción</span>
                        <span class="detail-value" id="detail_descripcion"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value" id="detail_estatus_area"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Bienes asignados</span>
                        <span class="detail-value" id="detail_bienes_count"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Personal</span>
                        <span class="detail-value" id="detail_personal_count"></span>
                    </div>
                </div>
            </div>
            <div class="component-modal-footer">
                <button type="button" class="btn-secundario" onclick="closeModal('modalAreaDetails')">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal Importar Areas -->
    <div id="modalImportar" class="component-modal">
        <div class="component-modal-content component-modal-sm">
            <div class="component-modal-header">
                <h2>Importar &aacute;reas</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalImportar')">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.areas.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="component-modal-body">
                    <p style="color:var(--muted);margin-bottom:16px;line-height:1.5;">
                        Sube un archivo Excel (.xlsx, .xls) o CSV con los datos de las &aacute;reas.
                        <a href="{{ route('admin.areas.template') }}" style="color:var(--primary);font-weight:600;">Descargar plantilla</a>
                    </p>
                    <div class="form-group">
                        <label for="archivo">Archivo *</label>
                        <input type="file" id="archivo" name="archivo" accept=".csv,.xlsx,.xls,.txt" required>
                    </div>
                    <div class="form-group">
                        <label>Formato esperado</label>
                        <div style="font-size:13px;color:var(--muted);background:var(--surface-strong);padding:12px;border-radius:10px;border:1px solid var(--border);line-height:1.6;">
                            <strong style="color:var(--text);">Columnas:</strong> nombre_area, descripcion, estatus<br>
                            <span style="font-size:12px;">* nombre_area es obligatorio<br>* estatus: Activa o Inactiva</span>
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
        function openModalArea() {
            var form = document.getElementById('formArea');
            form.action = '{{ route("admin.areas.store") }}';
            document.getElementById('modalAreaMethod').value = 'POST';
            document.getElementById('area_edit_id').value = '';
            document.getElementById('modalAreaTitle').textContent = 'Agregar área';
            document.querySelector('#modalArea .btn-agregar').textContent = 'Guardar';
            document.getElementById('nombre_area').value = '';
            document.getElementById('descripcion').value = '';
            document.getElementById('estatus').value = 'Activa';
            openModal('modalArea');
        }

        function openDetailsArea(button) {
            document.getElementById('detail_nombre_area').textContent = button.dataset.nombre_area || 'N/A';
            document.getElementById('detail_descripcion').textContent = button.dataset.descripcion || 'Sin descripción';
            document.getElementById('detail_estatus_area').textContent = button.dataset.estatus || 'N/A';
            document.getElementById('detail_bienes_count').textContent = button.dataset.bienes_count || '0';
            document.getElementById('detail_personal_count').textContent = button.dataset.personal_count || '0';
            openModal('modalAreaDetails');
        }

        function editArea(button) {
            document.getElementById('modalAreaMethod').value = 'PUT';
            document.getElementById('formArea').action = '{{ url("/areas") }}/' + button.dataset.id_area;
            document.getElementById('area_edit_id').value = button.dataset.id_area || '';
            document.getElementById('modalAreaTitle').textContent = 'Editar área';
            document.querySelector('#modalArea .btn-agregar').textContent = 'Guardar cambios';
            document.getElementById('nombre_area').value = button.dataset.nombre_area || '';
            document.getElementById('descripcion').value = button.dataset.descripcion || '';
            document.getElementById('estatus').value = button.dataset.estatus || 'Activa';
            openModal('modalArea');
        }
        function confirmDeleteArea(button) {
            var bienes = parseInt(button.dataset.bienes_count || '0', 10);
            var personal = parseInt(button.dataset.personal_count || '0', 10);
            var message = '¿Está seguro de eliminar esta área?';

            if (bienes > 0 || personal > 0) {
                message = 'Esta área tiene ' + bienes + ' bien(es) y ' + personal + ' personal(es) asignado(s). Al eliminarla, estos registros quedarán sin área. ¿Desea continuar?';
            }

            showConfirm(message, function () {
                button.closest('form').submit();
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (@json($errors->any())) {
                if (document.getElementById('area_edit_id').value) {
                    document.getElementById('modalAreaTitle').textContent = 'Editar área';
                    document.querySelector('#modalArea .btn-agregar').textContent = 'Guardar cambios';
                }
                openModal('modalArea');
            }
        });
    </script>

    <style>
        .cards {
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .cards .card {
            padding: 14px;
            border-radius: 14px;
        }
        .cards .card-top {
            margin-bottom: 12px;
            gap: 10px;
        }
        .cards .area-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 16px;
        }
        .cards .card h3 {
            font-size: 16px;
            margin-bottom: 4px;
        }
        .cards .card .responsable {
            font-size: 13px;
        }
        .cards .linea {
            margin: 10px 0;
        }
        .cards .datos {
            gap: 6px;
            margin-bottom: 10px;
        }
        .cards .info-item {
            font-size: 12px;
            gap: 8px;
        }
        .cards .details-btn {
            min-height: 32px;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 8px;
        }
        @media (max-width: 1200px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
