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
                <a href="{{ route('admin.bienes.papelera') }}" class="btn-secundario btn-danger"><i class="fa-solid fa-trash-can"></i> Papelera</a>
                <a href="{{ route('admin.reportes.export', 'excel') }}" class="btn-secundario"><i class="fa-solid fa-file-export"></i> Exportar</a>
                <button type="button" class="btn-agregar" onclick="openModalImportar()">
                    <i class="fa-solid fa-file-import"></i> Importar
                </button>
                <a href="javascript:void(0)" onclick="printAllBarcodes()" class="btn-secundario">
                    <i class="fa-solid fa-print"></i> Imprimir todos
                </a>
                <button type="button" class="btn-agregar" onclick="openModalBien()">
                    <i class="fa-solid fa-plus"></i> Nuevo bien
                </button>
            </div>
        @endif
    </div>

    <div class="page-actions-extra" id="pageActionsExtra" style="display:none;">
        @if(Auth::user()->isAdmin())
            <button type="button" class="btn-secundario btn-danger" id="deleteSelectedBtn" onclick="deleteSelected()" disabled>
                <i class="fa-solid fa-trash"></i> Papelera
            </button>
            <button type="button" class="btn-secundario btn-danger" onclick="destroyAllBien()">
                <i class="fa-solid fa-trash"></i> Enviar todos a papelera
            </button>
        @endif
        <button type="button" class="btn-secundario" id="printSelectedBtn" onclick="printSelectedBarcodes()" disabled>
            <i class="fa-solid fa-print"></i> Imprimir c&oacute;digos
        </button>
    </div>

    <div class="filter-columns-container">
        <div class="filter-columns-row">
            <div class="filter-col">
                <label>No. Inventario</label>
                <input type="text" class="column-filter" data-column="0" placeholder="INV-00001">
            </div>
            <div class="filter-col">
                <label>ID SEP</label>
                <input type="text" class="column-filter" data-column="1" placeholder="SEP-123">
            </div>
            <div class="filter-col">
                <label>Nombre del bien</label>
                <input type="text" class="column-filter" data-column="2" placeholder="Computadora">
            </div>
            <div class="filter-col">
                <label>Marca</label>
                <input type="text" class="column-filter" data-column="3" placeholder="HP, Dell...">
            </div>
            <div class="filter-col">
                <label>Modelo</label>
                <input type="text" class="column-filter" data-column="4" placeholder="OptiPlex">
            </div>
            <div class="filter-col">
                <label>&Aacute;rea</label>
                <input type="text" class="column-filter" data-column="5" placeholder="Direcci&oacute;n">
            </div>
            <div class="filter-col">
                <label>Estado</label>
                <select class="column-filter column-filter-select" data-column="6">
                    <option value="">Todos</option>
                    <option value="Disponible">Disponible</option>
                    <option value="Asignado">Asignado</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Baja">Baja</option>
                </select>
            </div>
            <div class="filter-col">
                <label>Responsable</label>
                <input type="text" class="column-filter" data-column="8" placeholder="Juan P&eacute;rez">
            </div>
            <div class="filter-col" style="display:flex;align-items:end;">
                <button type="button" class="btn-secundario" onclick="clearFilters()" style="width:100%;padding:8px 10px;font-size:13px;line-height:1.2;border-radius:10px;">
                    <i class="fa-solid fa-filter-circle-xmark"></i> Limpiar filtros
                </button>
            </div>
        </div>
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
                    <th>Responsable</th>
                    <th>Acciones</th>
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
                        <td>{{ $bien->area?->nombre_area ?? 'Sin área' }}</td>
                        <td><span class="estado {{ strtolower($bien->estatus) }}">{{ $bien->estatus }}</span></td>
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
                                data-barcode_uri="{{ $bien->qr_data_uri }}"
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

        <div class="paginacion" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <span>Mostrando {{ $bienes->firstItem() ?? 0 }} - {{ $bienes->lastItem() ?? 0 }} de {{ $bienes->total() }} bienes</span>
            <form method="GET" action="{{ route('admin.bienes') }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                @if(request()->filled('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if(request()->filled('estatus'))
                    <input type="hidden" name="estatus" value="{{ request('estatus') }}">
                @endif
                <label for="per_page" style="font-size:13px;font-weight:600;color:var(--muted);">Por página</label>
                <select id="per_page" name="per_page" onchange="this.form.submit()" style="min-width:90px;padding:8px 10px;border:1px solid var(--border);border-radius:10px;background:var(--surface-strong);color:var(--text);">
                    <option value="10" {{ ($perPage ?? 25) == 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ ($perPage ?? 25) == 20 ? 'selected' : '' }}>20</option>
                    <option value="25" {{ ($perPage ?? 25) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ ($perPage ?? 25) == 50 ? 'selected' : '' }}>50</option>
                </select>
            </form>
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
                        <input type="text" id="id_sep" name="id_sep" maxlength="30" pattern="[a-zA-Z0-9\-\.\/]*" placeholder="Max. 30 caracteres">
                        <small class="field-hint" style="color:var(--muted);font-size:12px;margin-top:4px;display:block;">Solo alfanum&eacute;ricos, guiones, puntos y barras. Opcional.</small>
                    </div>
                    <div class="form-group">
                        <label for="nombre_bien">Nombre del bien *</label>
                        <input type="text" id="nombre_bien" name="nombre_bien" required minlength="3" maxlength="255">
                        <small class="field-hint" style="color:var(--muted);font-size:12px;margin-top:4px;display:block;">M&iacute;nimo 3 caracteres.</small>
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
                        <input type="text" id="modelo" name="modelo" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="serie">Serie</label>
                        <input type="text" id="serie" name="serie" maxlength="150">
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
                        <span class="detail-label">C&oacute;digo QR</span>
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

    <style>
        .detail-qr-img {
            width: 160px;
            height: 160px;
            object-fit: contain;
            display: block;
            margin: 4px auto 8px;
        }
        .detail-qr-code {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }
    </style>
    <form id="bulkDeleteForm" method="POST" action="{{ route('admin.bienes.bulk-delete') }}" style="display:none;">
        @csrf
        <input type="hidden" id="bulkDeleteIds" name="ids" value="">
    </form>

    <form id="destroyAllForm" method="POST" action="{{ route('admin.bienes.destroy-all') }}" style="display:none;">
        @csrf
    </form>

    <script>
        const bienStoreUrl = "{{ route('admin.bienes.store') }}";
        const bienBaseUrl = "{{ url('/bienes') }}";
        const bulkDeleteUrl = "{{ route('admin.bienes.bulk-delete') }}";
        const totalBienes = {{ $bienes->total() }};

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

        document.getElementById('formBien').addEventListener('submit', function (e) {
            var nombre = document.getElementById('nombre_bien').value.trim();
            var idSep = document.getElementById('id_sep').value.trim();
            if (nombre.length < 3) {
                e.preventDefault();
                showAlert('El nombre del bien debe tener al menos 3 caracteres.');
                return;
            }
            if (idSep.length > 30) {
                e.preventDefault();
                showAlert('El ID SEP no puede exceder 30 caracteres.');
                return;
            }
            if (idSep && !/^[a-zA-Z0-9\-\.\/]+$/.test(idSep)) {
                e.preventDefault();
                showAlert('El ID SEP solo puede contener letras, n&uacute;meros, guiones, puntos y barras.');
                return;
            }
        });

        function openDetailsBien(button) {
            document.getElementById('detail_no_inventario').textContent = button.dataset.no_inventario || 'N/A';
            document.getElementById('detail_id_sep').textContent = button.dataset.id_sep || 'N/A';
            document.getElementById('detail_nombre_bien').textContent = button.dataset.nombre_bien || 'N/A';
            document.getElementById('detail_marca').textContent = button.dataset.marca || 'N/A';
            document.getElementById('detail_modelo').textContent = button.dataset.modelo || 'N/A';
            document.getElementById('detail_serie').textContent = button.dataset.serie || 'N/A';
            document.getElementById('detail_area_nombre').textContent = button.dataset.area_nombre || 'Sin área';
            document.getElementById('detail_personal_nombre').textContent = button.dataset.personal_nombre || 'Sin asignar';
            document.getElementById('detail_estatus').textContent = button.dataset.estatus || 'N/A';
            var codigo = button.dataset.codigo_barras;
            var barcodeUri = button.dataset.barcode_uri;
            var barcodeEl = document.getElementById('detail_codigo_barras');
            if (codigo && barcodeUri) {
                barcodeEl.innerHTML = '<img src="' + barcodeUri + '" alt="' + codigo + '" class="detail-qr-img"><span class="detail-qr-code">' + codigo + '</span>';
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
                var delBtn = document.getElementById('deleteSelectedBtn');
                if (delBtn) {
                    delBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Papelera (' + count + ')';
                    delBtn.disabled = false;
                }
                var printBtn = document.getElementById('printSelectedBtn');
                if (printBtn) {
                    printBtn.innerHTML = '<i class="fa-solid fa-print"></i> Imprimir c&oacute;digos (' + count + ')';
                    printBtn.disabled = false;
                }
            } else {
                extra.style.display = 'none';
                var delBtn = document.getElementById('deleteSelectedBtn');
                if (delBtn) delBtn.disabled = true;
                var printBtn = document.getElementById('printSelectedBtn');
                if (printBtn) printBtn.disabled = true;
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

        function destroyAllBien() {
            if (totalBienes === 0) {
                showAlert('No hay bienes para enviar a la papelera.');
                return;
            }
            showConfirm('¿Está seguro de enviar los ' + totalBienes + ' bien(es) a la papelera? Se enviarán todos sin importar los filtros o página actual.', function () {
                document.getElementById('destroyAllForm').submit();
            });
        }

        function printSelectedBarcodes() {
            var checked = document.querySelectorAll('.barcode-checkbox:checked');
            if (checked.length === 0) {
                showAlert('Selecciona al menos un bien para imprimir sus códigos.');
                return;
            }
            var ids = Array.from(checked).map(function(cb) { return cb.value; }).join(',');
            printBarcodesUrl("{{ route('admin.bienes.barcodes') }}?ids=" + ids);
        }

        function printAllBarcodes() {
            printBarcodesUrl("{{ route('admin.bienes.barcodes') }}?all=1");
        }

        function printBarcodesUrl(url) {
            var overlay = document.getElementById('printOverlay');
            var content = document.getElementById('printContent');
            content.innerHTML = '<p style="padding:40px;text-align:center;font-size:16px;">Generando c&oacute;digos QR...</p>';
            overlay.style.display = 'flex';

            var apiUrl = "{{ route('admin.bienes.barcodes-json') }}?" + url.split('?')[1];
            fetch(apiUrl, { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(bienes) {
                    content.innerHTML = '';
                    if (bienes.length === 0) {
                        overlay.style.display = 'none';
                        showAlert('No se encontraron c&oacute;digos para imprimir.');
                        return;
                    }
                    var pageSize = 20;
                    var totalPages = Math.ceil(bienes.length / pageSize);
                    for (var p = 0; p < totalPages; p++) {
                        var pageDiv = document.createElement('div');
                        pageDiv.className = 'page';
                        var start = p * pageSize;
                        var end = Math.min(start + pageSize, bienes.length);
                        for (var row = 0; row < 5; row++) {
                            var rowDiv = document.createElement('div');
                            rowDiv.className = 'page-row';
                            for (var col = 0; col < 4; col++) {
                                var index = start + (row * 4) + col;
                                if (index >= end) break;
                                var label = document.createElement('div');
                                label.className = 'label';
                                label.innerHTML = '<div class="qr-box">' + generateQRSvg(bienes[index].codigo_barras) + '</div>' +
                                    '<div class="label-idsep">' + (bienes[index].id_sep || bienes[index].codigo_barras || '') + '</div>';
                                rowDiv.appendChild(label);
                            }
                            if (rowDiv.childElementCount > 0) {
                                pageDiv.appendChild(rowDiv);
                            }
                        }
                        content.appendChild(pageDiv);
                    }
                })
                .catch(function() {
                    overlay.style.display = 'none';
                    showAlert('Error al generar los c&oacute;digos.');
                });
        }

        function generateQRSvg(text) {
            var typeNumber = 0;
            var errorCorrectionLevel = 'M';
            var qr = qrcode(typeNumber, errorCorrectionLevel);
            qr.addData(text);
            qr.make();
            var modules = qr.getModuleCount();
            var size = 140;
            var cellSize = size / modules;
            var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + size + ' ' + size + '" width="' + size + '" height="' + size + '">';
            for (var r = 0; r < modules; r++) {
                for (var c = 0; c < modules; c++) {
                    if (qr.isDark(r, c)) {
                        svg += '<rect x="' + (c * cellSize) + '" y="' + (r * cellSize) + '" width="' + cellSize + '" height="' + cellSize + '" fill="#000"/>';
                    }
                }
            }
            svg += '</svg>';
            return svg;
        }

        function closePrintOverlay() {
            document.getElementById('printOverlay').style.display = 'none';
            document.getElementById('printContent').innerHTML = '';
        }

        function doPrint() {
            window.print();
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

    <div id="printOverlay" style="display:none;">
        <div class="print-overlay-bar">
            <span id="printOverlayTitle">Vista previa de impresi&oacute;n</span>
            <div>
                <button onclick="doPrint()" class="btn-print-action"><i class="fa-solid fa-print"></i> Imprimir</button>
                <button onclick="closePrintOverlay()" class="btn-print-close"><i class="fa-solid fa-xmark"></i> Cerrar</button>
            </div>
        </div>
        <div id="printContent" class="print-overlay-content"></div>
    </div>

    <style>
        #printOverlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #f0f0f0; z-index: 99999; flex-direction: column;
            overflow: hidden;
        }
        .print-overlay-bar {
            background: #1e293b; color: #fff; padding: 12px 20px;
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .print-overlay-bar span { font-size: 15px; font-weight: 600; }
        .print-overlay-bar div { display: flex; gap: 10px; }
        .btn-print-action {
            background: #2563eb; color: #fff; border: none; padding: 8px 18px;
            border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-print-action:hover { background: #1d4ed8; }
        .btn-print-close {
            background: #475569; color: #fff; border: none; padding: 8px 18px;
            border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-print-close:hover { background: #334155; }
        .print-overlay-content {
            flex: 1; overflow-y: auto; padding: 20px;
        }
        .print-overlay-content .page {
            display: flex;
            flex-direction: column;
            gap: 0;
            page-break-after: always; justify-content: center; align-content: start;
        }
        .print-overlay-content .page-row {
            display: grid;
            grid-template-columns: repeat(4, 50mm);
            justify-content: center;
        }
        .print-overlay-content .page:last-child { page-break-after: auto; }
        .print-overlay-content .label {
            width: 50mm; height: 50mm; padding: 2mm;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            break-inside: avoid; box-sizing: border-box;
            border: 0.3px solid #e5e5e5;
        }
        .print-overlay-content .qr-box {
            width: 40mm;
            height: 40mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .print-overlay-content .qr-box svg {
            width: 40mm;
            height: 40mm;
            display: block;
        }
        .print-overlay-content .label-idsep {
            margin-top: 0.8mm;
            font-size: 8px;
            line-height: 1;
            font-weight: 700;
            color: #111;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }

        @media print {
            .contenedor > :not(.contenido),
            .contenido > :not(#printOverlay),
            .sidebar,
            .sidebar-overlay,
            .hamburger-fixed,
            .component-modal {
                display: none !important;
            }
            .contenedor,
            .contenido {
                display: block !important;
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            #printOverlay {
                display: block !important;
                position: static;
                background: #fff;
                overflow: visible;
            }
            .print-overlay-bar { display: none !important; }
            .print-overlay-content { overflow: visible; padding: 0; }
            .print-overlay-content .page {
                display: flex;
                flex-direction: column;
                gap: 0;
                page-break-after: always; justify-content: center; align-content: start;
            }
            .print-overlay-content .page-row {
                display: grid;
                grid-template-columns: repeat(4, 50mm);
                justify-content: center;
            }
            .print-overlay-content .page:last-child { page-break-after: auto; }
            .print-overlay-content .label {
                width: 50mm; height: 50mm; padding: 2mm;
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                break-inside: avoid; box-sizing: border-box;
                border: 0.3px solid #e5e5e5;
            }
            .print-overlay-content .qr-box {
                width: 40mm;
                height: 40mm;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .print-overlay-content .qr-box svg {
                width: 40mm;
                height: 40mm;
                display: block;
            }
            .print-overlay-content .label-idsep {
                margin-top: 0.8mm;
                font-size: 8px;
                line-height: 1;
                font-weight: 700;
                color: #111;
                text-align: center;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                width: 100%;
            }
            @page { margin: 5mm; size: letter; }
        }
    </style>
@endsection