@extends('layouts.admin')

@section('title', 'Gestion de Areas')

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
    </style>

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
        <form method="GET" class="buscar-form">
            <div class="input-buscar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por nombre de &aacute;rea..." value="{{ $search ?? '' }}">
            </div>
            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
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
                            <button type="button" class="action-btn"
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
                                <button type="submit" class="action-btn action-danger" aria-label="Eliminar" onclick="return confirm('¿Está seguro?')"><i class="fa-solid fa-trash"></i></button>
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
                        <strong>{{ $area->bienes_count ?? 0 }}</strong>
                    </div>

                    <div class="info-item">
                        <span>Personal</span>
                        <strong>{{ $area->personal_count ?? 0 }}</strong>
                    </div>
                </div>

                <button type="button" class="details-btn" onclick="openDetailsArea(this)"
                    data-nombre_area="{{ $area->nombre_area }}"
                    data-descripcion="{{ $area->descripcion }}"
                    data-estatus="{{ $area->estatus }}"
                >Ver detalles</button>
            </article>
        @empty
            <p style="grid-column: 1/-1; text-align: center; padding: 40px;">No hay áreas registradas</p>
        @endforelse
    </div>

    <!-- Modal Agregar/Editar Área -->
    <div id="modalArea" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalAreaTitle">Agregar área</h2>
                <button onclick="closeModalArea()">&times;</button>
            </div>
            <form id="formArea" method="POST" action="{{ route('admin.areas.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalAreaMethod" value="POST">
                <div class="modal-body">
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
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModalArea()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Detalles Área -->
    <div id="modalAreaDetails" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalles del área</h2>
                <button onclick="closeDetailsArea()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nombre del área</label>
                    <p id="detail_nombre_area"></p>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <p id="detail_descripcion"></p>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <p id="detail_estatus_area"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDetailsArea()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        function openModalArea() {
            var form = document.getElementById('formArea');
            form.action = '{{ route("admin.areas.store") }}';
            document.getElementById('modalAreaMethod').value = 'POST';
            document.getElementById('modalAreaTitle').textContent = 'Agregar área';
            document.querySelector('#modalArea .btn-submit').textContent = 'Guardar';
            document.getElementById('nombre_area').value = '';
            document.getElementById('descripcion').value = '';
            document.getElementById('estatus').value = 'Activa';
            document.getElementById('modalArea').classList.add('show');
        }

        function closeModalArea() {
            document.getElementById('modalArea').classList.remove('show');
        }

        function openDetailsArea(button) {
            document.getElementById('detail_nombre_area').textContent = button.dataset.nombre_area || 'N/A';
            document.getElementById('detail_descripcion').textContent = button.dataset.descripcion || 'Sin descripción';
            document.getElementById('detail_estatus_area').textContent = button.dataset.estatus || 'N/A';
            document.getElementById('modalAreaDetails').classList.add('show');
        }

        function closeDetailsArea() {
            document.getElementById('modalAreaDetails').classList.remove('show');
        }

        function editArea(button) {
            document.getElementById('modalArea').classList.add('show');
            document.getElementById('modalAreaMethod').value = 'PUT';
            document.getElementById('formArea').action = '{{ url("/areas") }}/' + button.dataset.id_area;
            document.getElementById('modalAreaTitle').textContent = 'Editar área';
            document.querySelector('.btn-submit').textContent = 'Guardar cambios';
            document.getElementById('nombre_area').value = button.dataset.nombre_area || '';
            document.getElementById('descripcion').value = button.dataset.descripcion || '';
            document.getElementById('estatus').value = button.dataset.estatus || 'Activa';
        }

        window.onclick = function(event) {
            const modalArea = document.getElementById('modalArea');
            const modalDetails = document.getElementById('modalAreaDetails');
            if (event.target === modalArea) {
                closeModalArea();
            }
            if (event.target === modalDetails) {
                closeDetailsArea();
            }
        }
    </script>
@endsection
