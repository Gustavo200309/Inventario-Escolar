@extends('layouts.admin')

@section('title', 'Detalle del Bien')

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

    @if($errors->any())
        <div class="component-alert component-alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span class="component-alert-content">{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="header">
        <div>
            <h1>{{ $bien->nombre_bien }}</h1>
            <p>{{ $bien->no_inventario ?: 'Sin inventario' }} &middot; {{ $bien->marcaRelacion?->nombre_marca ?? $bien->marca ?? 'Sin marca' }}</p>
        </div>

        <div class="header-right">
            @include('admin.partials.header-logos')

            <div class="page-actions">
                @if(Auth::user()->isAdmin())
                    <button type="button" class="btn-agregar" onclick="openModalMovimiento()">
                        <i class="fa-solid fa-arrows-rotate"></i> Asignar / Reasignar
                    </button>
                @endif
                <a href="{{ route('admin.bienes') }}" class="btn-secundario">
                    <i class="fa-solid fa-arrow-left"></i> Volver a bienes
                </a>
            </div>
        </div>
    </div>

    <section class="public-detail-card" style="padding:22px;">
        <header class="public-detail-header">
            <h2>Informaci&oacute;n del bien</h2>
        </header>

        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">No. Inventario</span>
                <span class="detail-value">{{ $bien->no_inventario ?: 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">ID SEP</span>
                <span class="detail-value">{{ $bien->id_sep ?: 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Marca</span>
                <span class="detail-value">{{ $bien->marcaRelacion?->nombre_marca ?? $bien->marca ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Modelo</span>
                <span class="detail-value">{{ $bien->modelo ?: 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Serie</span>
                <span class="detail-value">{{ $bien->serie ?: 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Fecha de adquisici&oacute;n</span>
                <span class="detail-value">{{ $bien->adq ?: 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Valor</span>
                <span class="detail-value">{{ $bien->valor ? '$' . number_format((float) $bien->valor, 2) : 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Fecha de registro</span>
                <span class="detail-value">{{ $bien->fecha_registro ? $bien->fecha_registro->format('d/m/Y H:i') : 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">&Aacute;rea</span>
                <span class="detail-value">{{ $bien->area?->nombre_area ?? 'Sin área' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Responsable</span>
                <span class="detail-value">{{ $bien->personal?->nombre_completo ?? 'Sin asignar' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value"><span class="estado {{ strtolower($bien->estatus) }}">{{ $bien->estatus ?: 'N/A' }}</span></span>
            </div>
            <div class="detail-item" style="text-align:center;">
                <span class="detail-label">C&oacute;digo QR</span>
                @if($bien->qr_data_uri)
                    <img src="{{ $bien->qr_data_uri }}" alt="{{ $bien->codigo_barras }}" class="detail-qr-img">
                    <span class="detail-qr-code">{{ $bien->codigo_barras }}</span>
                @else
                    <span class="detail-value">{{ $bien->codigo_barras ?: 'N/A' }}</span>
                @endif
            </div>
        </div>
    </section>

    <section class="public-detail-card" style="padding:22px;margin-top:24px;">
        <header class="public-detail-header">
            <h2>Historial de movimientos</h2>
            <p style="color:var(--muted);font-size:14px;margin-top:4px;">
                Todas las modificaciones realizadas a este bien, agrupadas por tipo.
            </p>
        </header>

        @forelse($bien->historiales->groupBy('tipo_movimiento') as $tipo => $items)
            <div class="historial-group">
                <div class="historial-group-header">
                    <span class="tag {{ \Illuminate\Support\Str::slug($tipo) }}">{{ $tipo }}</span>
                    <span class="historial-group-count">{{ $items->count() }} registro(s)</span>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Responsable anterior</th>
                                <th>Responsable nuevo</th>
                                <th>&Aacute;rea anterior</th>
                                <th>&Aacute;rea nueva</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items->sortByDesc('fecha_movimiento') as $h)
                                <tr>
                                    <td>{{ $h->fecha_movimiento?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                                    <td>{{ $h->personalAnterior?->nombre_completo ?? '-' }}</td>
                                    <td>{{ $h->personalNuevo?->nombre_completo ?? '-' }}</td>
                                    <td>{{ $h->areaAnterior?->nombre_area ?? '-' }}</td>
                                    <td>{{ $h->areaNueva?->nombre_area ?? '-' }}</td>
                                    <td>{{ $h->observaciones ?? 'Sin observaciones' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p style="text-align:center;padding:30px;color:var(--muted);">No hay movimientos registrados para este bien.</p>
        @endforelse
    </section>

    <!-- Modal Registrar Movimiento -->
    <div id="modalMovimiento" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2>Registrar movimiento</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalMovimiento')">&times;</button>
            </div>
            <form id="formMovimiento" method="POST" action="{{ route('admin.asignaciones.store') }}">
                @csrf
                <input type="hidden" name="id_bien" value="{{ $bien->id_bien }}">
                <input type="hidden" name="redirect_to" value="{{ route('admin.bienes.show', $bien, false) }}">
                <div class="component-modal-body">
                    <div class="form-group">
                        <div style="background:var(--surface);padding:12px;border-radius:8px;border:1px solid var(--border);">
                            <small style="color:var(--muted);">Bien: <strong>{{ $bien->nombre_bien }} ({{ $bien->no_inventario }})</strong></small><br>
                            <small style="color:var(--muted);">&Aacute;rea actual: <strong>{{ $bien->area?->nombre_area ?? 'Sin área' }}</strong></small><br>
                            <small style="color:var(--muted);">Responsable actual: <strong>{{ $bien->personal?->nombre_completo ?? 'Sin asignar' }}</strong></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tipo_movimiento">Tipo de movimiento *</label>
                        <select id="tipo_movimiento" name="tipo_movimiento" required onchange="onTipoMovimientoChange()">
                            <option value="">Seleccionar tipo</option>
                            <option value="Asignacion">Asignaci&oacute;n</option>
                            <option value="Reasignacion">Reasignaci&oacute;n</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Cambio de personal">Cambio de personal</option>
                            <option value="Cambio de area">Cambio de &aacute;rea</option>
                            <option value="Devolucion">Devoluci&oacute;n</option>
                        </select>
                    </div>

                    <div class="form-group" id="areaDestinoGroup">
                        <label for="id_area_nueva">&Aacute;rea de destino</label>
                        <select id="id_area_nueva" name="id_area_nueva" onchange="onAreaDestinoChange()">
                            <option value="">Misma &aacute;rea (solo cambio de personal)</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id_area }}">{{ $area->nombre_area }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="personalDestinoGroup">
                        <label for="id_personal_nuevo">Nuevo responsable</label>
                        <select id="id_personal_nuevo" name="id_personal_nuevo">
                            <option value="">Seleccionar responsable</option>
                            @foreach($personals as $personal)
                                <option value="{{ $personal->id_personal }}" data-area="{{ $personal->id_area }}">{{ $personal->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="devolucionInfo" style="display:none;">
                        <p style="color:var(--muted);"><i class="fa-solid fa-info-circle"></i> La devoluci&oacute;n dejar&aacute; el bien sin responsable ni &aacute;rea asignada.</p>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="4" maxlength="500"></textarea>
                        <small class="field-hint" style="color:var(--muted);font-size:12px;margin-top:4px;display:block;">Opcional. M&aacute;ximo 500 caracteres.</small>
                    </div>
                </div>
                <div class="component-modal-footer">
                    <button type="button" class="btn-secundario" onclick="closeModal('modalMovimiento')">Cancelar</button>
                    <button type="submit" class="btn-agregar">Registrar movimiento</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const personalByArea = {};
        @foreach($personals as $personal)
            @php $areaId = $personal->id_area ?? 'null'; @endphp
            if (!personalByArea[{{ $areaId }}]) personalByArea[{{ $areaId }}] = [];
            personalByArea[{{ $areaId }}].push({
                id: {{ $personal->id_personal }},
                nombre: @json($personal->nombre_completo)
            });
        @endforeach

        function openModalMovimiento() {
            document.getElementById('formMovimiento').reset();
            document.getElementById('tipo_movimiento').value = '';
            document.getElementById('devolucionInfo').style.display = 'none';
            document.getElementById('areaDestinoGroup').style.display = '';
            document.getElementById('personalDestinoGroup').style.display = '';
            document.getElementById('id_area_nueva').disabled = false;
            document.getElementById('id_personal_nuevo').disabled = false;
            filterPersonalByArea(null);
            openModal('modalMovimiento');
        }

        function onTipoMovimientoChange() {
            var tipo = document.getElementById('tipo_movimiento').value;
            var esDevolucion = tipo === 'Devolucion';

            document.getElementById('devolucionInfo').style.display = esDevolucion ? 'block' : 'none';
            document.getElementById('areaDestinoGroup').style.display = esDevolucion ? 'none' : '';
            document.getElementById('personalDestinoGroup').style.display = esDevolucion ? 'none' : '';
            document.getElementById('id_area_nueva').disabled = esDevolucion;
            document.getElementById('id_personal_nuevo').disabled = esDevolucion;

            if (esDevolucion) {
                document.getElementById('id_area_nueva').value = '';
                document.getElementById('id_personal_nuevo').value = '';
            } else {
                onAreaDestinoChange();
            }
        }

        function onAreaDestinoChange() {
            var areaId = document.getElementById('id_area_nueva').value;
            document.getElementById('id_personal_nuevo').value = '';
            filterPersonalByArea(areaId ? parseInt(areaId) : null);
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

            options.sort(function (a, b) { return a.nombre.localeCompare(b.nombre); });

            options.forEach(function (p) {
                var opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.nombre;
                select.appendChild(opt);
            });

            if (currentVal) select.value = currentVal;
        }
    </script>

    <style>
        .public-detail-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
        }
        .public-detail-header {
            border-bottom: 1px solid var(--border);
            margin-bottom: 22px;
            padding-bottom: 16px;
        }
        .public-detail-header h2 {
            color: var(--primary-dark);
            font-size: 20px;
            line-height: 1.2;
            margin: 0;
        }
        .historial-group {
            margin-bottom: 24px;
        }
        .historial-group-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .historial-group-count {
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }
        .detail-qr-img {
            width: 140px;
            height: 140px;
            object-fit: contain;
            display: block;
            margin: 4px auto 8px;
        }
        .detail-qr-code {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            word-break: break-all;
        }
    </style>
@endsection
