@extends('layouts.admin')

@section('title', 'Bienes Pendientes')

@section('content')
    <div class="header">
        <div>
            <h1>Bienes Pendientes</h1>
            <p>Gestiona los bienes que requieren atencion</p>
        </div>
    </div>

    @if(session('success'))
        <div class="component-alert component-alert-success" style="margin-bottom:20px;">
            <div class="component-alert-content">{{ session('success') }}</div>
        </div>
    @endif

    <div class="stats">
        <article class="stat-card">
            <h3>Total pendientes</h3>
            <span class="green">{{ count($pendientes ?? []) }}</span>
        </article>

        <article class="stat-card">
            <h3>Prioridad alta</h3>
            <span class="red">{{ collect($pendientes ?? [])->where('prioridad', 'Alta')->count() }}</span>
        </article>

        <article class="stat-card">
            <h3>Sin asignar</h3>
            <span class="green">{{ collect($pendientes ?? [])->where('razon', 'Sin asignar')->count() }}</span>
        </article>
    </div>

    <div class="search-box">
        <form method="GET" class="search" style="display:contents;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Buscar bienes pendientes..." value="{{ $search ?? '' }}">
            <select name="prioridad">
                <option value="">Todas las prioridades</option>
                <option value="Alta" {{ ($prioridad ?? '') === 'Alta' ? 'selected' : '' }}>Alta</option>
                <option value="Media" {{ ($prioridad ?? '') === 'Media' ? 'selected' : '' }}>Media</option>
                <option value="Baja" {{ ($prioridad ?? '') === 'Baja' ? 'selected' : '' }}>Baja</option>
            </select>
            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
            <a href="{{ route('admin.pendientes') }}" class="btn-secundario"><i class="fa-solid fa-rotate-left"></i> Limpiar</a>
        </form>
    </div>

    @forelse($pendientes as $bien)
        <article class="item">
            <div class="priority {{ strtolower($bien->prioridad ?? 'media') }}">{{ $bien->prioridad ?? 'Media' }}</div>

            <div class="item-top">
                <div class="alert-icon">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>

                <div class="item-info">
                    <h3>{{ $bien->nombre_bien }}</h3>
                    <div class="code">{{ $bien->no_inventario }}</div>

                    <div class="details">
                        <div>
                            <p>Razon</p>
                            <strong>{{ $bien->razon ?? 'N/A' }}</strong>
                        </div>

                        <div>
                            <p>Estado</p>
                            <strong>{{ $bien->estatus ?? 'Pendiente' }}</strong>
                        </div>

                        <div>
                            <p>Fecha de registro</p>
                            <strong>{{ $bien->fecha_registro ? \Carbon\Carbon::parse($bien->fecha_registro)->format('d/m/Y') : 'N/A' }}</strong>
                        </div>
                    </div>

                    <div class="buttons">
                        <button type="button" class="btn btn-outline" onclick="viewDetailsPendiente(this)"
                            data-nombre="{{ $bien->nombre_bien }}"
                            data-inventario="{{ $bien->no_inventario }}"
                            data-codigo="{{ $bien->codigo_barras }}"
                            data-razon="{{ $bien->razon }}"
                            data-prioridad="{{ $bien->prioridad }}"
                            data-estatus="{{ $bien->estatus }}"
                            data-area="{{ $bien->area?->nombre_area }}"
                            data-responsable="{{ $bien->personal?->nombre }}"
                            data-fecha="{{ $bien->fecha_registro ? \Carbon\Carbon::parse($bien->fecha_registro)->format('d/m/Y') : 'N/A' }}">
                            <i class="fa-solid fa-eye"></i>
                            Ver detalles
                        </button>

                        @if(Auth::user()->isAdmin())
                            <button type="button" class="btn btn-green" onclick="openModalResolver('{{ route('admin.pendientes.resolver', $bien) }}')">
                                <i class="fa-solid fa-pen"></i>
                                Resolver
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </article>
    @empty
        <p style="text-align: center; padding: 40px;">No hay bienes pendientes registrados</p>
    @endforelse

    <div id="modalPendienteDetails" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2>Detalle del pendiente</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalPendienteDetails')">&times;</button>
            </div>
            <div class="component-modal-body">
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Bien</span><span class="detail-value" id="detail_nombre"></span></div>
                    <div class="detail-item"><span class="detail-label">No. inventario</span><span class="detail-value" id="detail_inventario"></span></div>
                    <div class="detail-item"><span class="detail-label">Codigo de barras</span><span class="detail-value" id="detail_codigo"></span></div>
                    <div class="detail-item"><span class="detail-label">Razon</span><span class="detail-value" id="detail_razon"></span></div>
                    <div class="detail-item"><span class="detail-label">Prioridad</span><span class="detail-value" id="detail_prioridad"></span></div>
                    <div class="detail-item"><span class="detail-label">Estado</span><span class="detail-value" id="detail_estatus"></span></div>
                    <div class="detail-item"><span class="detail-label">Area</span><span class="detail-value" id="detail_area"></span></div>
                    <div class="detail-item"><span class="detail-label">Responsable</span><span class="detail-value" id="detail_responsable"></span></div>
                    <div class="detail-item"><span class="detail-label">Fecha</span><span class="detail-value" id="detail_fecha"></span></div>
                </div>
            </div>
            <div class="component-modal-footer">
                <button type="button" class="btn-secundario" onclick="closeModal('modalPendienteDetails')">Cerrar</button>
            </div>
        </div>
    </div>

    <div id="modalResolver" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2>Resolver bien pendiente</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalResolver')">&times;</button>
            </div>
            <form id="formResolver" method="POST">
                @csrf
                @method('PUT')
                <div class="component-modal-body">
                    <p style="color:var(--muted);margin-bottom:16px;">Selecciona como resolver este bien.</p>
                    <div class="form-group">
                        <label for="accion">Accion a realizar *</label>
                        <select id="accion" name="accion" required>
                            <option value="">Seleccionar accion</option>
                            <option value="Asignar">Asignar a personal</option>
                            <option value="Mantenimiento">Enviar a mantenimiento</option>
                            <option value="Reparar">Reparar</option>
                            <option value="Descartar">Descartar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notas">Notas</label>
                        <textarea id="notas" name="notas" rows="4" maxlength="500" placeholder="Notas sobre la acci&oacute;n realizada"></textarea>
                        <small class="field-hint" style="color:var(--muted);font-size:12px;margin-top:4px;display:block;">Opcional. M&aacute;ximo 500 caracteres.</small>
                    </div>
                    <div class="form-group">
                        <label for="nuevo_estatus">Nuevo estado *</label>
                        <select id="nuevo_estatus" name="nuevo_estatus" required>
                            <option value="">Seleccionar estado</option>
                            <option value="Resuelto">Resuelto</option>
                            <option value="En revision">En revision</option>
                            <option value="En mantenimiento">En mantenimiento</option>
                            <option value="Disponible">Disponible</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </div>
                </div>
                <div class="component-modal-footer">
                    <button type="button" class="btn-secundario" onclick="closeModal('modalResolver')">Cancelar</button>
                    <button type="submit" class="btn-agregar">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewDetailsPendiente(button) {
            document.getElementById('detail_nombre').textContent = button.dataset.nombre || 'N/A';
            document.getElementById('detail_inventario').textContent = button.dataset.inventario || 'N/A';
            document.getElementById('detail_codigo').textContent = button.dataset.codigo || 'N/A';
            document.getElementById('detail_razon').textContent = button.dataset.razon || 'N/A';
            document.getElementById('detail_prioridad').textContent = button.dataset.prioridad || 'N/A';
            document.getElementById('detail_estatus').textContent = button.dataset.estatus || 'N/A';
            document.getElementById('detail_area').textContent = button.dataset.area || 'Sin area';
            document.getElementById('detail_responsable').textContent = button.dataset.responsable || 'Sin responsable';
            document.getElementById('detail_fecha').textContent = button.dataset.fecha || 'N/A';
            openModal('modalPendienteDetails');
        }

        function openModalResolver(action) {
            document.getElementById('formResolver').action = action;
            openModal('modalResolver');
        }
    </script>
@endsection
