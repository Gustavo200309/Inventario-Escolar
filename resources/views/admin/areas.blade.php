@extends('layouts.admin')

@section('title', 'Gestion de Areas')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de &Aacute;reas</h1>
            <p>Administra las &aacute;reas institucionales</p>
        </div>

        @if(Auth::user()->isAdmin())
            <button type="button" class="btn-agregar" onclick="openModalArea()">
                <i class="fa-solid fa-plus"></i>
                Agregar &aacute;rea
            </button>
        @endif
    </div>

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
                            <form method="POST" action="{{ route('admin.areas.destroy', $area) }}" style="display:inline;" onsubmit="return confirmAction(event, '¿Eliminar esta área?', 'Sí, eliminar', 'Cancelar', 'error')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn action-danger" aria-label="Eliminar"><i class="fa-solid fa-trash"></i></button>
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
            <form id="formArea" method="POST" action="{{ route('admin.areas.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalAreaMethod" value="POST">
                <div class="component-modal-body">
                    <div class="form-group">
                        <label for="nombre_area">Nombre del área *</label>
                        <input type="text" id="nombre_area" name="nombre_area" required>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="estatus">Estado *</label>
                        <select id="estatus" name="estatus" required>
                            <option value="Activa">Activa</option>
                            <option value="Inactiva">Inactiva</option>
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

    <script>
        function openModalArea() {
            var form = document.getElementById('formArea');
            form.action = '{{ route("admin.areas.store") }}';
            document.getElementById('modalAreaMethod').value = 'POST';
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
            document.getElementById('modalAreaTitle').textContent = 'Editar área';
            document.querySelector('#modalArea .btn-agregar').textContent = 'Guardar cambios';
            document.getElementById('nombre_area').value = button.dataset.nombre_area || '';
            document.getElementById('descripcion').value = button.dataset.descripcion || '';
            document.getElementById('estatus').value = button.dataset.estatus || 'Activa';
            openModal('modalArea');
        }
    </script>
@endsection
