@extends('layouts.admin')

@section('title', 'Gestion de Asignaciones')

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
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #d8ddd4; border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #2f943c; box-shadow: 0 0 0 3px rgba(47,148,60,0.1); }
        .modal-footer { display: flex; gap: 12px; justify-content: flex-end; padding-top: 15px; border-top: 1px solid #e8ede4; }
        .modal-footer button { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .btn-cancel { background: #e8ede4; color: #2f3e34; }
        .btn-cancel:hover { background: #dae2d6; }
        .btn-submit { background: #2f943c; color: white; }
        .btn-submit:hover { background: #21692c; transform: translateY(-2px); }
        .alert-error { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; }
    </style>

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
        <div class="alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="setting-card" style="margin-bottom: 20px; border-color: var(--success-border); background: var(--success-bg); color: var(--success-text);">
            {{ session('success') }}
        </div>
    @endif

    <div class="buscador">
        <form method="GET" class="buscar-form">
            <div class="input-buscar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por bien, responsable o &aacute;rea..." value="{{ $search ?? '' }}">
            </div>
            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
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
                            <button type="button" onclick="openDetailsAsignacion(this)" title="Ver detalles" style="background: none; border: none; cursor: pointer; color: #2f943c; margin-right: 8px;"
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
                                <button type="button" onclick="editAsignacion(this)" title="Editar" style="background: none; border: none; cursor: pointer; color: #2f943c; margin-right: 8px;"
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

    <div id="modalAsignacion" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalAsignacionTitle">Nueva asignaci&oacute;n</h2>
                <button type="button" onclick="closeModalAsignacion()">&times;</button>
            </div>
            <form id="formAsignacion" method="POST" action="{{ route('admin.asignaciones.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalAsignacionMethod" value="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="id_bien">Bien *</label>
                        <select id="id_bien" name="id_bien" required>
                            <option value="">Seleccionar bien</option>
                            @foreach($bienes ?? [] as $bien)
                                <option value="{{ $bien->id_bien }}">{{ $bien->nombre_bien }} - {{ $bien->no_inventario }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_personal_nuevo">Personal</label>
                        <select id="id_personal_nuevo" name="id_personal_nuevo">
                            <option value="">Sin responsable</option>
                            @foreach($personals ?? [] as $personal)
                                <option value="{{ $personal->id_personal }}">{{ $personal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_area_nueva">&Aacute;rea</label>
                        <select id="id_area_nueva" name="id_area_nueva">
                            <option value="">Sin area</option>
                            @foreach($areas ?? [] as $area)
                                <option value="{{ $area->id_area }}">{{ $area->nombre_area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tipo_movimiento">Tipo de movimiento *</label>
                        <select id="tipo_movimiento" name="tipo_movimiento" required>
                            <option value="Asignacion">Asignaci&oacute;n</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Devolucion">Devoluci&oacute;n</option>
                            <option value="Reasignacion">Reasignaci&oacute;n</option>
                            <option value="Cambio de area">Cambio de &aacute;rea</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModalAsignacion()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalAsignacionDetails" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalles de la asignaci&oacute;n</h2>
                <button type="button" onclick="closeDetailsAsignacion()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group"><label>Bien</label><p id="detail_asignacion_bien"></p></div>
                <div class="form-group"><label>No. inventario</label><p id="detail_asignacion_inventario"></p></div>
                <div class="form-group"><label>Responsable</label><p id="detail_asignacion_personal"></p></div>
                <div class="form-group"><label>&Aacute;rea</label><p id="detail_asignacion_area"></p></div>
                <div class="form-group"><label>Ultimo movimiento</label><p id="detail_asignacion_fecha"></p></div>
                <div class="form-group"><label>Tipo</label><p id="detail_asignacion_tipo"></p></div>
                <div class="form-group"><label>Estado</label><p id="detail_asignacion_estatus"></p></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDetailsAsignacion()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        const asignacionStoreUrl = @json(route('admin.asignaciones.store'));
        const asignacionBaseUrl = @json(url('/asignaciones'));

        function openModalAsignacion() {
            document.getElementById('modalAsignacion').classList.add('show');
            document.getElementById('formAsignacion').reset();
            document.getElementById('formAsignacion').action = asignacionStoreUrl;
            document.getElementById('modalAsignacionTitle').textContent = 'Nueva asignacion';
            document.getElementById('modalAsignacionMethod').value = 'POST';
            document.getElementById('id_bien').disabled = false;
            document.querySelector('#modalAsignacion .btn-submit').textContent = 'Guardar';
        }

        function closeModalAsignacion() {
            document.getElementById('modalAsignacion').classList.remove('show');
        }

        function openDetailsAsignacion(button) {
            document.getElementById('detail_asignacion_bien').textContent = button.dataset.nombre_bien || 'N/A';
            document.getElementById('detail_asignacion_inventario').textContent = button.dataset.no_inventario || 'N/A';
            document.getElementById('detail_asignacion_personal').textContent = button.dataset.personal_nombre || 'Sin responsable';
            document.getElementById('detail_asignacion_area').textContent = button.dataset.area_nombre || 'Sin area';
            document.getElementById('detail_asignacion_fecha').textContent = button.dataset.fecha_movimiento || 'Sin movimientos';
            document.getElementById('detail_asignacion_tipo').textContent = button.dataset.tipo_movimiento || 'N/A';
            document.getElementById('detail_asignacion_estatus').textContent = button.dataset.estatus || 'N/A';
            document.getElementById('modalAsignacionDetails').classList.add('show');
        }

        function closeDetailsAsignacion() {
            document.getElementById('modalAsignacionDetails').classList.remove('show');
        }

        function editAsignacion(button) {
            openModalAsignacion();
            document.getElementById('formAsignacion').action = `${asignacionBaseUrl}/${button.dataset.id_bien}`;
            document.getElementById('modalAsignacionMethod').value = 'PUT';
            document.getElementById('modalAsignacionTitle').textContent = 'Editar asignacion';
            document.querySelector('#modalAsignacion .btn-submit').textContent = 'Guardar cambios';
            document.getElementById('id_bien').value = button.dataset.id_bien || '';
            document.getElementById('id_bien').disabled = true;
            document.getElementById('id_personal_nuevo').value = button.dataset.id_personal || '';
            document.getElementById('id_area_nueva').value = button.dataset.id_area || '';
            document.getElementById('tipo_movimiento').value = 'Reasignacion';
            document.getElementById('observaciones').value = '';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalAsignacion');
            const modalDetails = document.getElementById('modalAsignacionDetails');
            if (event.target === modal) {
                closeModalAsignacion();
            }
            if (event.target === modalDetails) {
                closeDetailsAsignacion();
            }
        }
    </script>
@endsection
