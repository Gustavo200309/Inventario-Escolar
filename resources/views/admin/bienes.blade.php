@extends('layouts.admin')

@section('title', 'Gestion de Bienes')

@section('content')
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

    <div class="header">
        <div>
            <h1>Gesti&oacute;n de Bienes</h1>
            <p>Administra inventario de bienes institucionales</p>
        </div>

        @if(Auth::user()->isAdmin())
            <div class="page-actions">
                <button type="button" class="btn-agregar" onclick="openModalImportar()">
                    <i class="fa-solid fa-file-import"></i>
                    Importar
                </button>
                <a href="{{ route('admin.reportes.export', 'excel') }}" class="btn-secundario"><i class="fa-solid fa-file-export"></i> Exportar</a>
                <a href="{{ route('admin.bienes.papelera') }}" class="btn-secundario btn-danger"><i class="fa-solid fa-trash-can"></i> Papelera</a>
                <button type="button" class="btn-secundario" onclick="clearFilters()"><i class="fa-solid fa-filter-circle-xmark"></i> Limpiar filtros</button>
                <button type="button" class="btn-agregar" onclick="openModalBien()">
                    <i class="fa-solid fa-plus"></i>
                    Agregar bien
                </button>
            </div>
        @endif
    </div>

    <div class="page-actions-extra" id="pageActionsExtra" style="display:none;">
        @if(Auth::user()->isAdmin())
            <button type="button" class="btn-secundario btn-danger" id="deleteSelectedBtn" onclick="deleteSelected()" disabled>
                <i class="fa-solid fa-trash"></i> Papelera
            </button>
        @endif
        <button type="button" class="btn-secundario" id="downloadBarcodesBtn" onclick="downloadSelectedBarcodes()" disabled>
            <i class="fa-solid fa-barcode"></i> Imprimir c&oacute;digos
        </button>
    </div>

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllCheckboxes(this)">
                    </th>
                    <th>No. Inventario</th>
                    <th>ID SEP</th>
                    <th>Nombre del bien</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>&Aacute;rea</th>
                    <th>Estado</th>
                    <th>C&oacute;digo de Barras</th>
                    <th>Responsable</th>
                    <th>Acciones</th>
                </tr>
                <tr class="filter-row">
                    <th></th>
                    <th><input type="text" class="column-filter" data-column="0" placeholder="INV-00001"></th>
                    <th><input type="text" class="column-filter" data-column="1" placeholder="SEP-123"></th>
                    <th><input type="text" class="column-filter" data-column="2" placeholder="Computadora"></th>
                    <th><input type="text" class="column-filter" data-column="3" placeholder="HP, Dell..."></th>
                    <th><input type="text" class="column-filter" data-column="4" placeholder="OptiPlex"></th>
                    <th><input type="text" class="column-filter" data-column="5" placeholder="Direcci&oacute;n"></th>
                    <th>
                        <select class="column-filter column-filter-select" data-column="6">
                            <option value="">Todos</option>
                            <option value="Disponible">Disponible</option>
                            <option value="Asignado">Asignado</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </th>
                    <th></th>
                    <th><input type="text" class="column-filter" data-column="8" placeholder="Juan P&eacute;rez"></th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse($bienes as $bien)
                    <tr>
                        <td>
                            <input type="checkbox" class="barcode-checkbox" value="{{ $bien->id_bien }}" onchange="updateSelectedButtons()">
                        </td>
                        <td>{{ $bien->no_inventario }}</td>
                        <td>{{ $bien->id_sep ?? 'N/A' }}</td>
                        <td>{{ $bien->nombre_bien }}</td>
                        <td>{{ $bien->marcaRelacion?->nombre_marca ?? $bien->marca ?? 'N/A' }}</td>
                        <td>{{ $bien->modelo ?? 'N/A' }}</td>
                        <td>{{ $bien->area?->nombre_area ?? 'Sin &aacute;rea' }}</td>
                        <td><span class="estado {{ strtolower($bien->estatus) }}">{{ $bien->estatus }}</span></td>
                        <td>
                            @if($bien->codigo_barras)
                                <img src="{{ $bien->barcode_data_uri }}" alt="{{ $bien->codigo_barras }}" class="barcode-img" style="height:14px;width:auto;">
                                <small style="display:block;color:var(--muted);font-size:11px;">{{ $bien->codigo_barras }}</small>
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $bien->personal?->nombre ?? 'Sin asignar' }}</td>
                        <td class="acciones">
                            <button type="button" class="action-btn action-view" title="Ver" onclick="openDetailsBien(this)"
                                data-id_bien="{{ $bien->id_bien }}"
                                data-no_inventario="{{ $bien->no_inventario }}"
                                data-id_sep="{{ $bien->id_sep }}"
                                data-nombre_bien="{{ $bien->nombre_bien }}"
                                data-marca="{{ $bien->marcaRelacion?->nombre_marca ?? $bien->marca }}"
                                data-modelo="{{ $bien->modelo }}"
                                data-serie="{{ $bien->serie }}"
                                data-id_area="{{ $bien->id_area }}"
                                data-area_nombre="{{ $bien->area?->nombre_area }}"
                                data-id_personal="{{ $bien->id_personal }}"
                                data-personal_nombre="{{ $bien->personal?->nombre }}"
                                data-estatus="{{ $bien->estatus }}"
                                data-codigo_barras="{{ $bien->codigo_barras }}"
                                data-barcode_uri="{{ $bien->barcode_data_uri }}"
                            ><i class="fa-solid fa-eye"></i></button>
                            @if(Auth::user()->isAdmin())
                                <button type="button" class="action-btn action-edit" title="Editar" onclick="editBien(this)"
                                    data-id_bien="{{ $bien->id_bien }}"
                                    data-no_inventario="{{ $bien->no_inventario }}"
                                    data-id_sep="{{ $bien->id_sep }}"
                                    data-nombre_bien="{{ $bien->nombre_bien }}"
                                    data-id_marca="{{ $bien->id_marca }}"
                                    data-marca="{{ $bien->marcaRelacion?->nombre_marca ?? $bien->marca }}"
                                    data-modelo="{{ $bien->modelo }}"
                                    data-serie="{{ $bien->serie }}"
                                    data-id_area="{{ $bien->id_area }}"
                                    data-id_personal="{{ $bien->id_personal }}"
                                    data-estatus="{{ $bien->estatus }}"
                                ><i class="fa-solid fa-pen"></i></button>
                                <form method="POST" action="{{ route('admin.bienes.destroy', $bien) }}" style="display:inline-flex;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-danger" title="Enviar a papelera" aria-label="Enviar a papelera" onclick="confirmThenSubmit(this, '¿Está seguro de enviar a la papelera?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align:center;padding:20px;">No hay bienes registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="paginacion">
            <span>Mostrando {{ $bienes->firstItem() ?? 0 }} - {{ $bienes->lastItem() ?? 0 }} de {{ $bienes->total() }} bienes</span>
            @if($bienes->hasPages())
                <div class="paginas">
                    @if($bienes->onFirstPage())
                        <button disabled>&laquo;</button>
                    @else
                        <a href="{{ $bienes->previousPageUrl() }}"><button>&laquo;</button></a>
                    @endif
                    @foreach($bienes->getUrlRange(max(1, $bienes->currentPage() - 2), min($bienes->lastPage(), $bienes->currentPage() + 2)) as $page => $url)
                        <a href="{{ $url }}"><button class="{{ $page == $bienes->currentPage() ? 'pagina-activa' : '' }}">{{ $page }}</button></a>
                    @endforeach
                    @if($bienes->hasMorePages())
                        <a href="{{ $bienes->nextPageUrl() }}"><button>&raquo;</button></a>
                    @else
                        <button disabled>&raquo;</button>
                    @endif
                </div>
            @endif
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
                    <div class="form-group" id="no_inventario_group" style="display:none;">
                        <label for="no_inventario">No. Inventario</label>
                        <input type="text" id="no_inventario" name="no_inventario" readonly>
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
                        <label for="id_marca">Marca</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <select id="id_marca" name="id_marca" style="flex:1;">
                                <option value="">Seleccionar marca</option>
                                @foreach($marcas as $marca)
                                    <option value="{{ $marca->id_marca }}">{{ $marca->nombre_marca }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn-secundario" onclick="openModalMarca()" title="Agregar marca" style="white-space:nowrap;padding:8px 12px;">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
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

    <!-- Modal Agregar Marca -->
    <div id="modalMarca" class="component-modal">
        <div class="component-modal-content component-modal-sm">
            <div class="component-modal-header">
                <h2>Agregar marca</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalMarca')">&times;</button>
            </div>
            <form id="formMarca" method="POST" action="{{ route('admin.marcas.store') }}">
                @csrf
                <div class="component-modal-body">
                    <div class="form-group">
                        <label for="nombre_marca">Nombre de la marca *</label>
                        <input type="text" id="nombre_marca" name="nombre_marca" required maxlength="100" placeholder="Ej: Dell, HP, Sony">
                    </div>
                </div>
                <div class="component-modal-footer">
                    <button type="button" class="btn-secundario" onclick="closeModal('modalMarca')">Cancelar</button>
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
                    <div class="detail-item" style="grid-column:1/-1;text-align:center;">
                        <span class="detail-label">C&oacute;digo de Barras</span>
                        <span class="detail-value" id="detail_codigo_barras"></span>
                    </div>
                </div>
            </div>
            <div class="component-modal-footer">
                <button type="button" class="btn-secundario" onclick="closeModal('modalBienDetails')">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal Importar Excel -->
    <div id="modalImportar" class="component-modal">
        <div class="component-modal-content component-modal-sm">
            <div class="component-modal-header">
                <h2>Importar bienes</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalImportar')">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.bienes.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="component-modal-body">
                    <p style="color:var(--muted);margin-bottom:16px;line-height:1.5;">
                        Sube un archivo Excel (.xlsx, .xls) o CSV con los datos de los bienes.
                        <a href="{{ route('admin.bienes.template') }}" style="color:var(--primary);font-weight:600;">Descargar plantilla</a>
                    </p>
                    <div class="form-group">
                        <label for="archivo">Archivo *</label>
                        <input type="file" id="archivo" name="archivo" accept=".csv,.xlsx,.xls,.txt" required>
                    </div>
                    <div class="form-group">
                        <label>Formato esperado</label>
                        <div style="font-size:13px;color:var(--muted);background:var(--surface-strong);padding:12px;border-radius:10px;border:1px solid var(--border);line-height:1.6;">
                            <strong style="color:var(--text);">Columnas:</strong> id_sep, nombre_bien, marca, modelo, serie, codigo_barras, id_area, id_personal, estatus<br>
                            <span style="font-size:12px;">* nombre_bien es obligatorio<br>* id_area y id_personal se buscan por nombre, no por ID</span>
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

    <form id="bulkDeleteForm" method="POST" action="{{ route('admin.bienes.bulk-delete') }}" style="display:none;">
        @csrf
        <input type="hidden" id="bulkDeleteIds" name="ids" value="">
    </form>

    <script>
        const bienStoreUrl = "{{ route('admin.bienes.store') }}";
        const bienBaseUrl = "{{ url('/bienes') }}";
        const barcodeDownloadUrl = "{{ route('admin.bienes.barcodes') }}";
        const bulkDeleteUrl = "{{ route('admin.bienes.bulk-delete') }}";

        const marcaStoreUrl = "{{ route('admin.marcas.store') }}";

        function openModalBien() {
            document.getElementById('modalBienMethod').value = 'POST';
            document.getElementById('formBien').reset();
            document.getElementById('formBien').action = bienStoreUrl;
            document.getElementById('modalBienTitle').textContent = 'Agregar bien';
            document.querySelector('#modalBien .btn-agregar').textContent = 'Guardar';
            document.getElementById('no_inventario_group').style.display = 'none';
            document.getElementById('id_marca').value = '';
            openModal('modalBien');
        }

        function editBien(button) {
            document.getElementById('modalBienMethod').value = 'PUT';
            document.getElementById('formBien').action = bienBaseUrl + '/' + button.dataset.id_bien;
            document.getElementById('formBien').reset();
            document.getElementById('modalBienTitle').textContent = 'Editar bien';
            document.querySelector('#modalBien .btn-agregar').textContent = 'Guardar cambios';
            document.getElementById('no_inventario_group').style.display = 'block';
            document.getElementById('no_inventario').value = button.dataset.no_inventario || '';
            document.getElementById('id_sep').value = button.dataset.id_sep || '';
            document.getElementById('nombre_bien').value = button.dataset.nombre_bien || '';
            document.getElementById('id_marca').value = button.dataset.id_marca || '';
            document.getElementById('modelo').value = button.dataset.modelo || '';
            document.getElementById('serie').value = button.dataset.serie || '';
            document.getElementById('id_area').value = button.dataset.id_area || '';
            document.getElementById('id_personal').value = button.dataset.id_personal || '';
            document.getElementById('estatus').value = button.dataset.estatus || 'Disponible';
            openModal('modalBien');
        }

        function openModalMarca() {
            document.getElementById('formMarca').reset();
            openModal('modalMarca');
        }

        document.getElementById('formMarca').addEventListener('submit', function (e) {
            e.preventDefault();
            var form = this;
            var formData = new FormData(form);
            fetch(marcaStoreUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    var select = document.getElementById('id_marca');
                    var option = document.createElement('option');
                    option.value = data.id_marca;
                    option.textContent = data.nombre_marca;
                    select.appendChild(option);
                    select.value = data.id_marca;
                    closeModal('modalMarca');
                }
            })
            .catch(function () {
                showAlert('Error al guardar la marca. Verifica que no exista ya.');
            });
        });

        function openDetailsBien(button) {
            document.getElementById('detail_no_inventario').textContent = button.dataset.no_inventario || 'N/A';
            document.getElementById('detail_id_sep').textContent = button.dataset.id_sep || 'N/A';
            document.getElementById('detail_nombre_bien').textContent = button.dataset.nombre_bien || 'N/A';
            document.getElementById('detail_marca').textContent = button.dataset.marca || 'N/A';
            document.getElementById('detail_modelo').textContent = button.dataset.modelo || 'N/A';
            document.getElementById('detail_serie').textContent = button.dataset.serie || 'N/A';
            document.getElementById('detail_area_nombre').textContent = button.dataset.area_nombre || 'Sin &aacute;rea';
            document.getElementById('detail_personal_nombre').textContent = button.dataset.personal_nombre || 'Sin asignar';
            document.getElementById('detail_estatus').textContent = button.dataset.estatus || 'N/A';
            var codigo = button.dataset.codigo_barras;
            var barcodeUri = button.dataset.barcode_uri;
            var barcodeEl = document.getElementById('detail_codigo_barras');
            if (codigo && barcodeUri) {
                barcodeEl.innerHTML = '<img src="' + barcodeUri + '" alt="' + codigo + '" class="barcode-img" style="height:28px;width:auto;"><br><small style="color:var(--muted);font-size:12px;">' + codigo + '</small>';
            } else {
                barcodeEl.textContent = codigo || 'N/A';
            }
            openModal('modalBienDetails');
        }

        function openModalImportar() {
            openModal('modalImportar');
        }

        function toggleAllCheckboxes(source) {
            var checkboxes = document.querySelectorAll('.barcode-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = source.checked;
            });
            updateSelectedButtons();
        }

        function updateSelectedButtons() {
            var checked = document.querySelectorAll('.barcode-checkbox:checked');
            var count = checked.length;
            var extra = document.getElementById('pageActionsExtra');

            if (count > 0) {
                extra.style.display = '';
                document.getElementById('downloadBarcodesBtn').innerHTML = '<i class="fa-solid fa-barcode"></i> Imprimir c&oacute;digos (' + count + ')';
                document.getElementById('downloadBarcodesBtn').disabled = false;
                var delBtn = document.getElementById('deleteSelectedBtn');
                if (delBtn) {
                    delBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Papelera (' + count + ')';
                    delBtn.disabled = false;
                }
            } else {
                extra.style.display = 'none';
                document.getElementById('downloadBarcodesBtn').disabled = true;
                var delBtn = document.getElementById('deleteSelectedBtn');
                if (delBtn) delBtn.disabled = true;
            }
        }

        function deleteSelected() {
            var checked = document.querySelectorAll('.barcode-checkbox:checked');
            if (checked.length === 0) return;
            var ids = Array.from(checked).map(function(cb) { return cb.value; });
            showConfirm('¿Está seguro de enviar ' + checked.length + ' bien(es) a la papelera?', function () {
                var form = document.getElementById('bulkDeleteForm');
                document.getElementById('bulkDeleteIds').value = JSON.stringify(ids);
                form.submit();
            });
        }

        function downloadSelectedBarcodes() {
            var checked = document.querySelectorAll('.barcode-checkbox:checked');
            if (checked.length === 0) {
                showAlert('Selecciona al menos un bien para imprimir su código de barras.');
                return;
            }
            var ids = Array.from(checked).map(function(cb) { return cb.value; }).join(',');
            var currentUrl = new URL(window.location.href);
            var params = currentUrl.searchParams;
            var fullUrl = barcodeDownloadUrl + '?ids=' + ids;
            if (params.get('search')) fullUrl += '&search=' + params.get('search');
            if (params.get('estatus')) fullUrl += '&estatus=' + params.get('estatus');
            window.open(fullUrl, '_blank');
        }

        function clearFilters() {
            document.querySelectorAll('.column-filter').forEach(function(input) {
                input.value = '';
            });
            filterTable();
        }

        document.querySelectorAll('.column-filter').forEach(function(input) {
            input.addEventListener('input', filterTable);
            input.addEventListener('change', filterTable);
        });

        function filterTable() {
            var filters = {};
            document.querySelectorAll('.column-filter').forEach(function(input) {
                var col = input.getAttribute('data-column');
                var val = input.value.trim().toLowerCase();
                if (val) filters[col] = val;
            });

            var rows = document.querySelectorAll('.tabla-contenedor tbody tr');
            rows.forEach(function(row) {
                if (row.querySelector('td[colspan]')) return;
                var show = true;
                for (var col in filters) {
                    var tdIndex = parseInt(col) + 1;
                    var td = row.querySelector('td:nth-child(' + (tdIndex + 1) + ')');
                    var text = td ? td.textContent.trim().toLowerCase() : '';
                    if (text.indexOf(filters[col]) === -1) {
                        show = false;
                        break;
                    }
                }
                row.style.display = show ? '' : 'none';
            });
        }
    </script>
@endsection