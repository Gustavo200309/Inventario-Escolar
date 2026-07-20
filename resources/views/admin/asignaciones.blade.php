@extends('layouts.admin')

@section('title', 'Gestion de Asignaciones')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de Asignaciones</h1>
            <p>Administra las asignaciones de bienes al personal</p>
        </div>

        @if(Auth::user()->isAdmin())
            <button type="button" class="btn-agregar" onclick="openModalAsignacion()">
                <i class="fa-solid fa-plus"></i>
                Nueva asignaci&oacute;n
            </button>
        @endif
    </div>

    @if ($errors->any())
        <div class="component-alert component-alert-error">
            <div class="component-alert-content">{{ $errors->first() }}</div>
        </div>
    @endif

    @if(session('success'))
        <div class="component-alert component-alert-success" style="margin-bottom:20px;">
            <div class="component-alert-content">{{ session('success') }}</div>
        </div>
    @endif

    <div class="buscador">
        <form method="GET" class="buscar-form" style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;width:100%;">
            <div class="input-buscar" style="flex:1;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por bien, responsable o &aacute;rea..." value="{{ $search ?? '' }}">
            </div>
            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
            @if($search)
                <a href="{{ route('admin.asignaciones') }}" class="btn-secundario"><i class="fa-solid fa-times"></i> Limpiar</a>
            @endif
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Bien</th>
                    <th>No. inventario</th>
                    <th>Responsable</th>
                    <th>&Aacute;rea</th>
                    <th>Ultimo movimiento</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($asignaciones as $bien)
                    <tr>
                        <td>{{ $bien->nombre_bien }}</td>
                        <td>{{ $bien->no_inventario }}</td>
                        <td>{{ $bien->personal?->nombre ?? 'Sin asignar' }}</td>
                        <td>{{ $bien->area?->nombre_area ?? 'Sin area' }}</td>
                        <td>{{ $bien->ultimoHistorial?->fecha_movimiento?->format('d/m/Y H:i') ?? 'Sin movimientos' }}</td>
                        <td><span class="status">{{ $bien->estatus }}</span></td>
                        <td class="acciones">
                            <button type="button" class="action-btn action-view" title="Ver detalles" onclick="openDetailsAsignacion(this)"
                                data-id_bien="{{ $bien->id_bien }}"
                                data-nombre_bien="{{ $bien->nombre_bien }}"
                                data-no_inventario="{{ $bien->no_inventario }}"
                                data-id_personal="{{ $bien->id_personal }}"
                                data-personal_nombre="{{ $bien->personal?->nombre }}"
                                data-id_area="{{ $bien->id_area }}"
                                data-area_nombre="{{ $bien->area?->nombre_area }}"
                                data-fecha_movimiento="{{ $bien->ultimoHistorial?->fecha_movimiento?->format('d/m/Y H:i') }}"
                                data-tipo_movimiento="{{ $bien->ultimoHistorial?->tipo_movimiento }}"
                                data-estatus="{{ $bien->estatus }}"
                            ><i class="fa-solid fa-eye"></i></button>
                            @if(Auth::user()->isAdmin())
                                <button type="button" class="action-btn action-edit" title="Editar" onclick="editAsignacion(this)"
                                    data-id_bien="{{ $bien->id_bien }}"
                                    data-id_personal="{{ $bien->id_personal }}"
                                    data-id_area="{{ $bien->id_area }}"
                                    data-estatus="{{ $bien->estatus }}"
                                ><i class="fa-solid fa-pen"></i></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">No hay bienes registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="tabla-footer">Mostrando {{ count($asignaciones ?? []) }} bienes</div>
    </div>

    <div id="modalAsignacion" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2 id="modalAsignacionTitle">Nueva asignaci&oacute;n</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalAsignacion')">&times;</button>
            </div>
            <form id="formAsignacion" method="POST" action="{{ route('admin.asignaciones.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalAsignacionMethod" value="POST">
                <input type="hidden" name="tipo_movimiento" id="tipo_movimiento" value="Asignacion">
                <div class="component-modal-body">
                    <div class="form-group">
                        <label for="id_bien">Bien *</label>
                        <select id="id_bien" name="id_bien" required onchange="onBienChange()">
                            <option value="">Seleccionar bien</option>
                            @foreach($bienes ?? [] as $bien)
                                <option value="{{ $bien->id_bien }}"
                                    data-area="{{ $bien->id_area }}"
                                    data-area_nombre="{{ $bien->area?->nombre_area }}"
                                    data-personal="{{ $bien->id_personal }}"
                                    data-personal_nombre="{{ $bien->personal?->nombre }}">{{ $bien->nombre_bien }} - {{ $bien->no_inventario }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="currentInfo" class="form-group" style="display:none;">
                        <div style="background:var(--surface);padding:12px;border-radius:8px;border:1px solid var(--border);">
                            <small style="color:var(--muted);">Área actual: <strong id="currentAreaLabel">—</strong></small><br>
                            <small style="color:var(--muted);">Responsable actual: <strong id="currentPersonalLabel">—</strong></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="id_area_nueva">&Aacute;rea de destino</label>
                        <select id="id_area_nueva" name="id_area_nueva" onchange="onAreaChange()">
                            <option value="">Misma &aacute;rea (solo cambio de personal)</option>
                            @foreach($areas ?? [] as $area)
                                <option value="{{ $area->id_area }}">{{ $area->nombre_area }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_personal_nuevo">Nuevo responsable</label>
                        <select id="id_personal_nuevo" name="id_personal_nuevo">
                            <option value="">Seleccionar responsable</option>
                            @foreach($personals ?? [] as $personal)
                                <option value="{{ $personal->id_personal }}" data-area="{{ $personal->id_area }}">{{ $personal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="devolucion_group" style="display:none;">
                        <p style="color:var(--muted);"><i class="fa-solid fa-info-circle"></i> La devoluci&oacute;n dejar&aacute; el bien sin responsable ni &aacute;rea asignada.</p>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="4" maxlength="500"></textarea>
                        <small class="field-hint" style="color:var(--muted);font-size:12px;margin-top:4px;display:block;">Opcional. M&aacute;ximo 500 caracteres.</small>
                    </div>
                </div>
                <div class="component-modal-footer">
                    <button type="button" class="btn-secundario" onclick="closeModal('modalAsignacion')">Cancelar</button>
                    <button type="submit" class="btn-agregar">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalAsignacionDetails" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2>Detalles de la asignaci&oacute;n</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalAsignacionDetails')">&times;</button>
            </div>
            <div class="component-modal-body">
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Bien</span><span class="detail-value" id="detail_asignacion_bien"></span></div>
                    <div class="detail-item"><span class="detail-label">No. inventario</span><span class="detail-value" id="detail_asignacion_inventario"></span></div>
                    <div class="detail-item"><span class="detail-label">Responsable</span><span class="detail-value" id="detail_asignacion_personal"></span></div>
                    <div class="detail-item"><span class="detail-label">&Aacute;rea</span><span class="detail-value" id="detail_asignacion_area"></span></div>
                    <div class="detail-item"><span class="detail-label">Ultimo movimiento</span><span class="detail-value" id="detail_asignacion_fecha"></span></div>
                    <div class="detail-item"><span class="detail-label">Tipo</span><span class="detail-value" id="detail_asignacion_tipo"></span></div>
                    <div class="detail-item"><span class="detail-label">Estado</span><span class="detail-value" id="detail_asignacion_estatus"></span></div>
                </div>
            </div>
            <div class="component-modal-footer">
                <button type="button" class="btn-secundario" onclick="closeModal('modalAsignacionDetails')">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        const asignacionStoreUrl = @json(route('admin.asignaciones.store'));
        const asignacionBaseUrl = @json(url('/asignaciones'));

        const bienData = {};
        @foreach($bienes ?? [] as $bien)
            bienData[{{ $bien->id_bien }}] = {
                area: {{ $bien->id_area ?? 'null' }},
                area_nombre: @json($bien->area?->nombre_area),
                personal: {{ $bien->id_personal ?? 'null' }},
                personal_nombre: @json($bien->personal?->nombre)
            };
        @endforeach

        const personalByArea = {};
        @foreach($personals ?? [] as $personal)
            @php $areaId = $personal->id_area ?? 'null'; @endphp
            if (!personalByArea[{{ $areaId }}]) personalByArea[{{ $areaId }}] = [];
            personalByArea[{{ $areaId }}].push({
                id: {{ $personal->id_personal }},
                nombre: @json($personal->nombre)
            });
        @endforeach

        function onBienChange() {
            var bienId = document.getElementById('id_bien').value;
            if (!bienId || !bienData[bienId]) {
                document.getElementById('currentInfo').style.display = 'none';
                return;
            }
            var data = bienData[bienId];
            document.getElementById('currentAreaLabel').textContent = data.area_nombre || 'Sin área';
            document.getElementById('currentPersonalLabel').textContent = data.personal_nombre || 'Sin responsable';
            document.getElementById('currentInfo').style.display = 'block';

            document.getElementById('id_area_nueva').value = '';
            document.getElementById('id_personal_nuevo').value = '';
            document.getElementById('devolucion_group').style.display = 'none';
            document.getElementById('id_area_nueva').disabled = false;
            document.getElementById('id_personal_nuevo').disabled = false;

            var tipo = document.getElementById('tipo_movimiento');
            if (data.area) {
                tipo.value = 'Cambio de personal';
            } else {
                tipo.value = 'Asignacion';
            }
            filterPersonalByArea(null);
        }

        function onAreaChange() {
            var bienId = document.getElementById('id_bien').value;
            var nuevaArea = document.getElementById('id_area_nueva').value;
            var tipo = document.getElementById('tipo_movimiento');

            if (!bienId || !bienData[bienId]) return;

            var data = bienData[bienId];
            var mismaArea = !nuevaArea || parseInt(nuevaArea) === (data.area ? parseInt(data.area) : -1);

            document.getElementById('id_personal_nuevo').value = '';

            if (mismaArea) {
                tipo.value = 'Cambio de personal';
                filterPersonalByArea(null);
            } else {
                tipo.value = 'Cambio de area';
                filterPersonalByArea(parseInt(nuevaArea));
            }
        }

        function filterPersonalByArea(areaId) {
            var select = document.getElementById('id_personal_nuevo');
            var currentVal = select.value;
            select.innerHTML = '<option value="">Seleccionar responsable</option>';

            var options = [];
            if (areaId !== null && personalByArea[areaId]) {
                options = personalByArea[areaId];
            } else {
                for (var key in personalByArea) {
                    options = options.concat(personalByArea[key]);
                }
            }

            options.sort(function(a,b) { return a.nombre.localeCompare(b.nombre); });

            options.forEach(function(p) {
                var opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.nombre;
                select.appendChild(opt);
            });

            if (currentVal) select.value = currentVal;
        }

        function openModalAsignacion() {
            document.getElementById('formAsignacion').reset();
            document.getElementById('formAsignacion').action = asignacionStoreUrl;
            document.getElementById('modalAsignacionTitle').textContent = 'Nueva asignacion';
            document.getElementById('modalAsignacionMethod').value = 'POST';
            document.getElementById('id_bien').disabled = false;
            document.getElementById('id_bien').value = '';
            document.getElementById('id_area_nueva').disabled = false;
            document.getElementById('id_personal_nuevo').disabled = false;
            document.getElementById('currentInfo').style.display = 'none';
            document.getElementById('devolucion_group').style.display = 'none';
            document.getElementById('tipo_movimiento').value = 'Asignacion';
            document.querySelector('#modalAsignacion .btn-agregar').textContent = 'Guardar';
            openModal('modalAsignacion');
        }

        function openDetailsAsignacion(button) {
            document.getElementById('detail_asignacion_bien').textContent = button.dataset.nombre_bien || 'N/A';
            document.getElementById('detail_asignacion_inventario').textContent = button.dataset.no_inventario || 'N/A';
            document.getElementById('detail_asignacion_personal').textContent = button.dataset.personal_nombre || 'Sin responsable';
            document.getElementById('detail_asignacion_area').textContent = button.dataset.area_nombre || 'Sin area';
            document.getElementById('detail_asignacion_fecha').textContent = button.dataset.fecha_movimiento || 'Sin movimientos';
            document.getElementById('detail_asignacion_tipo').textContent = button.dataset.tipo_movimiento || 'N/A';
            document.getElementById('detail_asignacion_estatus').textContent = button.dataset.estatus || 'N/A';
            openModal('modalAsignacionDetails');
        }

        function editAsignacion(button) {
            openModalAsignacion();
            document.getElementById('formAsignacion').action = asignacionBaseUrl + '/' + button.dataset.id_bien;
            document.getElementById('modalAsignacionMethod').value = 'PUT';
            document.getElementById('modalAsignacionTitle').textContent = 'Editar asignacion';
            document.querySelector('#modalAsignacion .btn-agregar').textContent = 'Guardar cambios';
            document.getElementById('id_bien').value = button.dataset.id_bien || '';
            document.getElementById('id_bien').disabled = true;
            document.getElementById('id_personal_nuevo').value = button.dataset.id_personal || '';
            document.getElementById('id_area_nueva').value = button.dataset.id_area || '';
            document.getElementById('observaciones').value = '';
            if (button.dataset.id_bien) onBienChange();
            if (button.dataset.id_area) onAreaChange();
        }
    </script>
@endsection
