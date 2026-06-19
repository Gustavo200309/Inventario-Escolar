@extends('layouts.admin')

@section('title', 'Gestion de Personal')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de Personal</h1>
            <p>Administra el personal y sus asignaciones</p>
        </div>

        @if(Auth::user()->isAdmin())
            <button type="button" class="btn-agregar" onclick="openModalPersonal()">
                <i class="fa-solid fa-plus"></i>
                Agregar personal
            </button>
        @endif
    </div>

    <div class="buscador">
        <form method="GET" class="buscar-form" style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;width:100%;">
            <div class="input-buscar" style="flex:1;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por nombre, cargo o &aacute;rea..." value="{{ $search ?? '' }}">
            </div>
            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
            @if($search)
                <a href="{{ route('admin.personal') }}" class="btn-secundario"><i class="fa-solid fa-times"></i> Limpiar</a>
            @endif
        </form>
    </div>

    <div class="personal-grid">
        @forelse($personals as $personal)
            <article class="card">
                <div class="card-top">
                    <div class="avatar">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span class="estado {{ strtolower($personal->estatus) }}">{{ $personal->estatus }}</span>
                </div>

                <h2 class="nombre">{{ $personal->nombre }} {{ $personal->apellido_paterno }} {{ $personal->apellido_materno }}</h2>
                <p class="puesto">{{ $personal->puesto }}</p>
                <p class="area">{{ $personal->area?->nombre_area ?? 'Sin área' }}</p>

                <div class="linea"></div>

                <div class="datos">
                    <div class="dato">
                        <span class="label">Correo</span>
                        <span class="valor">{{ $personal->correo ?? 'N/A' }}</span>
                    </div>
                    <div class="dato">
                        <span class="label">Tel&eacute;fono</span>
                        <span class="valor">{{ $personal->telefono ?? 'N/A' }}</span>
                    </div>
                    <div class="dato">
                        <span class="label">Bienes asignados</span>
                        <span class="valor">{{ $personal->bienes_count }}</span>
                    </div>
                </div>

                <div class="botones">
                    <button type="button" class="btn-ver" onclick="openDetailsPersonal(this)"
                        data-id_personal="{{ $personal->id_personal }}"
                        data-nombre="{{ $personal->nombre }} {{ $personal->apellido_paterno }} {{ $personal->apellido_materno }}"
                        data-puesto="{{ $personal->puesto }}"
                        data-correo="{{ $personal->correo }}"
                        data-telefono="{{ $personal->telefono }}"
                        data-area_nombre="{{ $personal->area?->nombre_area ?? 'Sin área' }}"
                        data-estatus="{{ $personal->estatus }}"
                        data-bienes_count="{{ $personal->bienes_count }}"
                    >Ver perfil</button>
                    @if(Auth::user()->isAdmin())
                        <button type="button" class="btn-icon action-edit"
                            data-id_personal="{{ $personal->id_personal }}"
                            data-nombre="{{ $personal->nombre }}"
                            data-apellido_paterno="{{ $personal->apellido_paterno }}"
                            data-apellido_materno="{{ $personal->apellido_materno }}"
                            data-puesto="{{ $personal->puesto }}"
                            data-correo="{{ $personal->correo }}"
                            data-telefono="{{ $personal->telefono }}"
                            data-id_area="{{ $personal->id_area }}"
                            data-estatus="{{ $personal->estatus }}"
                            onclick="editPersonal(this)"
                            aria-label="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" action="{{ route('admin.personal.destroy', $personal) }}" style="display:inline;" onsubmit="return confirmAction(event, '¿Eliminar este personal?', 'Sí, eliminar', 'Cancelar', 'error')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-delete action-danger" aria-label="Eliminar"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p style="grid-column: 1/-1; text-align: center; padding: 40px;">No hay personal registrado</p>
        @endforelse
    </div>

    <!-- Modal Agregar/Editar Personal -->
    <div id="modalPersonal" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2 id="modalPersonalTitle">Agregar personal</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalPersonal')">&times;</button>
            </div>
            <form id="formPersonal" method="POST" action="{{ route('admin.personal.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalPersonalMethod" value="POST">
                <div class="component-modal-body">
                    <div class="grid">
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="apellido_paterno">Apellido Paterno *</label>
                            <input type="text" id="apellido_paterno" name="apellido_paterno" required>
                        </div>
                        <div class="form-group">
                            <label for="apellido_materno">Apellido Materno</label>
                            <input type="text" id="apellido_materno" name="apellido_materno">
                        </div>
                        <div class="form-group">
                            <label for="puesto">Puesto *</label>
                            <input type="text" id="puesto" name="puesto" required>
                        </div>
                        <div class="form-group">
                            <label for="correo">Correo</label>
                            <input type="email" id="correo" name="correo">
                        </div>
                        <div class="form-group">
                            <label for="telefono">Tel&eacute;fono</label>
                            <input type="text" id="telefono" name="telefono">
                        </div>
                        <div class="form-group">
                            <label for="id_area">Área</label>
                            <select id="id_area" name="id_area">
                                <option value="">Seleccionar área</option>
                                @foreach($areas ?? [] as $area)
                                    <option value="{{ $area->id_area }}">{{ $area->nombre_area }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="estatus">Estado *</label>
                            <select id="estatus" name="estatus" required>
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="component-modal-footer">
                    <button type="button" class="btn-secundario" onclick="closeModal('modalPersonal')">Cancelar</button>
                    <button type="submit" class="btn-agregar">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Perfil Personal -->
    <div id="modalPersonalDetails" class="component-modal">
        <div class="component-modal-content component-modal-md">
            <div class="component-modal-header">
                <h2>Perfil del personal</h2>
                <button type="button" class="component-modal-close" onclick="closeModal('modalPersonalDetails')">&times;</button>
            </div>
            <div class="component-modal-body">
                <div class="detail-grid">
                    <div class="detail-item" style="grid-column:1/-1;">
                        <span class="detail-label">Nombre completo</span>
                        <span class="detail-value" id="detail_personal_nombre"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Puesto</span>
                        <span class="detail-value" id="detail_personal_puesto"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Área</span>
                        <span class="detail-value" id="detail_personal_area"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Correo</span>
                        <span class="detail-value" id="detail_personal_correo"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Teléfono</span>
                        <span class="detail-value" id="detail_personal_telefono"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value" id="detail_personal_estatus"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Bienes asignados</span>
                        <span class="detail-value" id="detail_personal_bienes"></span>
                    </div>
                </div>
            </div>
            <div class="component-modal-footer">
                <button type="button" class="btn-secundario" onclick="closeModal('modalPersonalDetails')">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        const personalStoreUrl = "{{ route('admin.personal.store') }}";
        const personalBaseUrl = "{{ url('/personal') }}";

        function openModalPersonal() {
            document.getElementById('formPersonal').reset();
            document.getElementById('modalPersonalTitle').textContent = 'Agregar personal';
            document.getElementById('formPersonal').action = personalStoreUrl;
            document.getElementById('modalPersonalMethod').value = 'POST';
            document.querySelector('#modalPersonal .btn-agregar').textContent = 'Guardar';
            openModal('modalPersonal');
        }

        function editPersonal(button) {
            openModal('modalPersonal');
            document.getElementById('modalPersonalTitle').textContent = 'Editar personal';
            document.getElementById('nombre').value = button.dataset.nombre || '';
            document.getElementById('apellido_paterno').value = button.dataset.apellido_paterno || '';
            document.getElementById('apellido_materno').value = button.dataset.apellido_materno || '';
            document.getElementById('puesto').value = button.dataset.puesto || '';
            document.getElementById('correo').value = button.dataset.correo || '';
            document.getElementById('telefono').value = button.dataset.telefono || '';
            document.getElementById('id_area').value = button.dataset.id_area || '';
            document.getElementById('estatus').value = button.dataset.estatus || 'Activo';
            document.getElementById('formPersonal').action = personalBaseUrl + '/' + button.dataset.id_personal;
            document.getElementById('modalPersonalMethod').value = 'PUT';
            document.querySelector('#modalPersonal .btn-agregar').textContent = 'Guardar cambios';
        }

        function openDetailsPersonal(button) {
            document.getElementById('detail_personal_nombre').textContent = button.dataset.nombre || 'N/A';
            document.getElementById('detail_personal_puesto').textContent = button.dataset.puesto || 'N/A';
            document.getElementById('detail_personal_area').textContent = button.dataset.area_nombre || 'Sin área';
            document.getElementById('detail_personal_correo').textContent = button.dataset.correo || 'N/A';
            document.getElementById('detail_personal_telefono').textContent = button.dataset.telefono || 'N/A';
            document.getElementById('detail_personal_estatus').textContent = button.dataset.estatus || 'N/A';
            document.getElementById('detail_personal_bienes').textContent = button.dataset.bienes_count || '0';
            openModal('modalPersonalDetails');
        }
    </script>
@endsection
