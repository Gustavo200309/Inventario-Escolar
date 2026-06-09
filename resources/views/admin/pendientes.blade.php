@extends('layouts.admin')

@section('title', 'Bienes Pendientes')

@section('content')
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal.show { display: flex; justify-content: center; align-items: center; }
        .modal-content { background-color: var(--surface); padding: 30px; border-radius: 12px; width: 90%; max-width: 640px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        .modal-header h2 { margin: 0; color: var(--primary-dark); font-size: 22px; }
        .modal-header button { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--muted); }
        .modal-footer { display: flex; gap: 12px; justify-content: flex-end; padding-top: 15px; border-top: 1px solid var(--border); }
        .btn-cancel { background: var(--hover); color: var(--text); }
        .btn-submit { background: var(--primary); color: white; }
        .info-text { color: var(--muted); margin: 10px 0; }
    </style>

    <div class="header">
        <div>
            <h1>Bienes Pendientes</h1>
            <p>Gestiona los bienes que requieren atencion</p>
        </div>
    </div>

    @if(session('success'))
        <div class="setting-card" style="margin-bottom: 20px; border-color: var(--success-border); background: var(--success-bg); color: var(--success-text);">
            {{ session('success') }}
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
        <form method="GET" class="search">
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

    <div id="modalPendienteDetails" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalle del pendiente</h2>
                <button type="button" onclick="closeDetailsPendiente()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="details">
                    <div><p>Bien</p><strong id="detail_nombre"></strong></div>
                    <div><p>No. inventario</p><strong id="detail_inventario"></strong></div>
                    <div><p>Codigo de barras</p><strong id="detail_codigo"></strong></div>
                    <div><p>Razon</p><strong id="detail_razon"></strong></div>
                    <div><p>Prioridad</p><strong id="detail_prioridad"></strong></div>
                    <div><p>Estado</p><strong id="detail_estatus"></strong></div>
                    <div><p>Area</p><strong id="detail_area"></strong></div>
                    <div><p>Responsable</p><strong id="detail_responsable"></strong></div>
                    <div><p>Fecha</p><strong id="detail_fecha"></strong></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDetailsPendiente()">Cerrar</button>
            </div>
        </div>
    </div>

    <div id="modalResolver" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Resolver bien pendiente</h2>
                <button type="button" onclick="closeModalResolver()">&times;</button>
            </div>
            <form id="formResolver" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p class="info-text">Selecciona como resolver este bien.</p>
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
                        <textarea id="notas" name="notas" rows="4" placeholder="Notas sobre la accion realizada"></textarea>
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
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModalResolver()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar cambios</button>
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
            document.getElementById('modalPendienteDetails').classList.add('show');
        }

        function closeDetailsPendiente() {
            document.getElementById('modalPendienteDetails').classList.remove('show');
        }

        function openModalResolver(action) {
            document.getElementById('modalResolver').classList.add('show');
            document.getElementById('formResolver').action = action;
        }

        function closeModalResolver() {
            document.getElementById('modalResolver').classList.remove('show');
        }

        window.addEventListener('click', function(event) {
            const resolver = document.getElementById('modalResolver');
            const details = document.getElementById('modalPendienteDetails');
            if (event.target === resolver) closeModalResolver();
            if (event.target === details) closeDetailsPendiente();
        });
    </script>
@endsection
