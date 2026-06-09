@extends('layouts.admin')

@section('title', 'Gestion de Bienes')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de Bienes</h1>
            <p>Administra inventario de bienes institucionales</p>
        </div>

        @if(Auth::user()->isAdmin())
            <button type="button" class="btn-agregar" onclick="openModalBien()">
                <i class="fa-solid fa-plus"></i>
                Agregar bien
            </button>
        @endif
    </div>

    <div class="buscador">
        <form method="GET" class="buscar-form">
            <div class="input-buscar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por nombre, serie o n&uacute;mero de inventario" value="{{ $search ?? '' }}">
            </div>

            <select name="estatus">
                <option value="">Todos los estados</option>
                <option value="Asignado" {{ ($estatus ?? '') === 'Asignado' ? 'selected' : '' }}>Asignado</option>
                <option value="Disponible" {{ ($estatus ?? '') === 'Disponible' ? 'selected' : '' }}>Disponible</option>
                <option value="Pendiente" {{ ($estatus ?? '') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
            </select>

            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>

            @if(Auth::user()->isAdmin())
                <a href="{{ route('admin.reportes.export', 'excel') }}" class="btn-secundario"><i class="fa-solid fa-file-export"></i> Exportar</a>
            @endif
        </form>
    </div>

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr>
                    <th>No. Inventario</th>
                    <th>ID SEP</th>
                    <th>Nombre del bien</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($bienes as $bien)
                    <tr>
                        <td>{{ $bien->no_inventario }}</td>
                        <td>{{ $bien->id_sep ?? 'N/A' }}</td>
                        <td>{{ $bien->nombre_bien }}</td>
                        <td>{{ $bien->marca ?? 'N/A' }}</td>
                        <td>{{ $bien->modelo ?? 'N/A' }}</td>
                        <td>${{ number_format($bien->valor ?? 0, 2) }}</td>
                        <td><span class="estado {{ strtolower($bien->estatus) }}">{{ $bien->estatus }}</span></td>
                        <td>{{ $bien->personal?->nombre ?? 'Sin asignar' }}</td>
                        <td class="acciones">
                            <button type="button" class="action-btn" title="Ver" onclick="openDetailsBien(this)"
                                data-id_bien="{{ $bien->id_bien }}"
                                data-no_inventario="{{ $bien->no_inventario }}"
                                data-id_sep="{{ $bien->id_sep }}"
                                data-nombre_bien="{{ $bien->nombre_bien }}"
                                data-marca="{{ $bien->marca }}"
                                data-modelo="{{ $bien->modelo }}"
                                data-serie="{{ $bien->serie }}"
                                data-valor="{{ $bien->valor }}"
                                data-id_area="{{ $bien->id_area }}"
                                data-area_nombre="{{ $bien->area?->nombre_area }}"
                                data-id_personal="{{ $bien->id_personal }}"
                                data-personal_nombre="{{ $bien->personal?->nombre }}"
                                data-estatus="{{ $bien->estatus }}"
                            ><i class="fa-solid fa-eye"></i></button>
                            @if(Auth::user()->isAdmin())
                                <button type="button" class="action-btn" title="Editar" onclick="editBien(this)"
                                    data-id_bien="{{ $bien->id_bien }}"
                                    data-no_inventario="{{ $bien->no_inventario }}"
                                    data-id_sep="{{ $bien->id_sep }}"
                                    data-nombre_bien="{{ $bien->nombre_bien }}"
                                    data-marca="{{ $bien->marca }}"
                                    data-modelo="{{ $bien->modelo }}"
                                    data-serie="{{ $bien->serie }}"
                                    data-valor="{{ $bien->valor }}"
                                    data-id_area="{{ $bien->id_area }}"
                                    data-id_personal="{{ $bien->id_personal }}"
                                    data-estatus="{{ $bien->estatus }}"
                                ><i class="fa-solid fa-pen"></i></button>
                                <form method="POST" action="{{ route('admin.bienes.destroy', $bien) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Eliminar" onclick="return confirm('¿Está seguro?')" style="background:none;border:none;cursor:pointer;color:#dc3545;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:20px;">No hay bienes registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="paginacion">
            <span>Mostrando {{ $bienes->count() }} bienes</span>
        </div>
    </div>

    <!-- Modal Agregar/Editar Bien -->
    <div id="modalBien" class="component-modal">
        <div class="component-modal-content">
            <div class="component-modal-header">
                <h2 id="modalBienTitle">Agregar bien</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalBien')">&times;</button>
            </div>
            <form id="formBien" method="POST" action="{{ route('admin.bienes.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalBienMethod" value="POST">
                <div class="component-modal-body">
                    <div class="form-group">
                        <label for="no_inventario">No. Inventario *</label>
                        <input type="text" id="no_inventario" name="no_inventario" required>
                    </div>
                    <div class="form-group">
                        <label for="id_sep">ID SEP</label>
                        <input type="text" id="id_sep" name="id_sep">
                    </div>
                    <div class="form-group">
                        <label for="nombre_bien">Nombre del bien *</label>
                        <input type="text" id="nombre_bien" name="nombre_bien" required>
                    </div>
                    <div class="form-group">
                        <label for="marca">Marca</label>
                        <input type="text" id="marca" name="marca">
                    </div>
                    <div class="form-group">
                        <label for="modelo">Modelo</label>
                        <input type="text" id="modelo" name="modelo">
                    </div>
                    <div class="form-group">
                        <label for="serie">Serie</label>
                        <input type="text" id="serie" name="serie">
                    </div>
                    <div class="form-group">
                        <label for="valor">Valor</label>
                        <input type="number" id="valor" name="valor" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="id_area">&Aacute;rea</label>
                        <select id="id_area" name="id_area">
                            <option value="">Seleccionar &aacute;rea</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id_area }}">{{ $area->nombre_area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_personal">Responsable</label>
                        <select id="id_personal" name="id_personal">
                            <option value="">Seleccionar personal</option>
                            @foreach($personals as $personal)
                                <option value="{{ $personal->id_personal }}">{{ $personal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estatus">Estado *</label>
                        <select id="estatus" name="estatus" required>
                            <option value="Disponible">Disponible</option>
                            <option value="Asignado">Asignado</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </div>
                </div>
                <div class="component-modal-footer">
                    <button type="button" class="btn-secundario" onclick="closeModal('modalBien')">Cancelar</button>
                    <button type="submit" class="btn-agregar">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Detalles Bien -->
    <div id="modalBienDetails" class="component-modal">
        <div class="component-modal-content">
            <div class="component-modal-header">
                <h2>Detalles del bien</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalBienDetails')">&times;</button>
            </div>
            <div class="component-modal-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">No. Inventario</span>
                        <span class="detail-value" id="detail_no_inventario"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">ID SEP</span>
                        <span class="detail-value" id="detail_id_sep"></span>
                    </div>
                    <div class="detail-item" style="grid-column:1/-1;">
                        <span class="detail-label">Nombre del bien</span>
                        <span class="detail-value" id="detail_nombre_bien"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Marca</span>
                        <span class="detail-value" id="detail_marca"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Modelo</span>
                        <span class="detail-value" id="detail_modelo"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Serie</span>
                        <span class="detail-value" id="detail_serie"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Valor</span>
                        <span class="detail-value" id="detail_valor"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">&Aacute;rea</span>
                        <span class="detail-value" id="detail_area_nombre"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Responsable</span>
                        <span class="detail-value" id="detail_personal_nombre"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value" id="detail_estatus"></span>
                    </div>
                </div>
            </div>
            <div class="component-modal-footer">
                <button type="button" class="btn-secundario" onclick="closeModal('modalBienDetails')">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        const bienStoreUrl = "{{ route('admin.bienes.store') }}";
        const bienBaseUrl = "{{ url('/bienes') }}";

        function openModalBien() {
            document.getElementById('modalBienMethod').value = 'POST';
            document.getElementById('formBien').reset();
            document.getElementById('formBien').action = bienStoreUrl;
            document.getElementById('modalBienTitle').textContent = 'Agregar bien';
            document.querySelector('#modalBien .btn-agregar').textContent = 'Guardar';
            openModal('modalBien');
        }

        function editBien(button) {
            document.getElementById('modalBienMethod').value = 'PUT';
            document.getElementById('formBien').action = bienBaseUrl + '/' + button.dataset.id_bien;
            document.getElementById('formBien').reset();
            document.getElementById('modalBienTitle').textContent = 'Editar bien';
            document.querySelector('#modalBien .btn-agregar').textContent = 'Guardar cambios';
            document.getElementById('no_inventario').value = button.dataset.no_inventario || '';
            document.getElementById('id_sep').value = button.dataset.id_sep || '';
            document.getElementById('nombre_bien').value = button.dataset.nombre_bien || '';
            document.getElementById('marca').value = button.dataset.marca || '';
            document.getElementById('modelo').value = button.dataset.modelo || '';
            document.getElementById('serie').value = button.dataset.serie || '';
            document.getElementById('valor').value = button.dataset.valor || '';
            document.getElementById('id_area').value = button.dataset.id_area || '';
            document.getElementById('id_personal').value = button.dataset.id_personal || '';
            document.getElementById('estatus').value = button.dataset.estatus || 'Disponible';
            openModal('modalBien');
        }

        function openDetailsBien(button) {
            document.getElementById('detail_no_inventario').textContent = button.dataset.no_inventario || 'N/A';
            document.getElementById('detail_id_sep').textContent = button.dataset.id_sep || 'N/A';
            document.getElementById('detail_nombre_bien').textContent = button.dataset.nombre_bien || 'N/A';
            document.getElementById('detail_marca').textContent = button.dataset.marca || 'N/A';
            document.getElementById('detail_modelo').textContent = button.dataset.modelo || 'N/A';
            document.getElementById('detail_serie').textContent = button.dataset.serie || 'N/A';
            document.getElementById('detail_valor').textContent = button.dataset.valor ? `$${parseFloat(button.dataset.valor).toFixed(2)}` : 'N/A';
            document.getElementById('detail_area_nombre').textContent = button.dataset.area_nombre || 'Sin &aacute;rea';
            document.getElementById('detail_personal_nombre').textContent = button.dataset.personal_nombre || 'Sin asignar';
            document.getElementById('detail_estatus').textContent = button.dataset.estatus || 'N/A';
            openModal('modalBienDetails');
        }
    </script>
@endsection
