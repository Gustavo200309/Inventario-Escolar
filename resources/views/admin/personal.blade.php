@extends('layouts.admin')

@section('title', 'Gestion de Personal')

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
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #d8ddd4; border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #2f943c; box-shadow: 0 0 0 3px rgba(47,148,60,0.1); }
        .modal-footer { display: flex; gap: 12px; justify-content: flex-end; padding-top: 15px; border-top: 1px solid #e8ede4; }
        .modal-footer button { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .btn-cancel { background: #e8ede4; color: #2f3e34; }
        .btn-cancel:hover { background: #dae2d6; }
        .btn-submit { background: #2f943c; color: white; }
        .btn-submit:hover { background: #21692c; transform: translateY(-2px); }
    </style>

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
        <form method="GET" class="buscar-form">
            <div class="input-buscar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por nombre, cargo o &aacute;rea..." value="{{ $search ?? '' }}">
            </div>
            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </form>
    </div>

    <div class="personal-grid">
        @forelse($personals as $personal)
            <article class="card">
                <div class="card-top">
                    <div class="avatar">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span class="estado">{{ $personal->estatus }}</span>
                </div>

                <h2 class="nombre">{{ $personal->nombre }}</h2>
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
                </div>

                <div class="botones">
                    <button type="button" class="btn-ver" onclick="openDetailsPersonal(this)"
                        data-id_personal="{{ $personal->id_personal }}"
                        data-nombre="{{ $personal->nombre }}"
                        data-apellido_paterno="{{ $personal->apellido_paterno }}"
                        data-apellido_materno="{{ $personal->apellido_materno }}"
                        data-puesto="{{ $personal->puesto }}"
                        data-correo="{{ $personal->correo }}"
                        data-telefono="{{ $personal->telefono }}"
                        data-area_nombre="{{ $personal->area?->nombre_area ?? 'Sin área' }}"
                        data-estatus="{{ $personal->estatus }}"
                    >Ver perfil</button>
                    @if(Auth::user()->isAdmin())
                        <button type="button" class="btn-icon"
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
                        <form method="POST" action="{{ route('admin.personal.destroy', $personal) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-delete" aria-label="Eliminar" onclick="return confirm('¿Está seguro?')"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p style="grid-column: 1/-1; text-align: center; padding: 40px;">No hay personal registrado</p>
        @endforelse
    </div>

    <!-- Modal Agregar/Editar Personal -->
    <div id="modalPersonal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalPersonalTitle">Agregar personal</h2>
                <button onclick="closeModalPersonal()">&times;</button>
            </div>
            <form id="formPersonal" method="POST" action="{{ route('admin.personal.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalPersonalMethod" value="POST">
                <div class="modal-body">
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
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModalPersonal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Perfil Personal -->
    <div id="modalPersonalDetails" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Perfil del personal</h2>
                <button onclick="closeDetailsPersonal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nombre completo</label>
                    <p id="detail_personal_nombre"></p>
                </div>
                <div class="form-group">
                    <label>Puesto</label>
                    <p id="detail_personal_puesto"></p>
                </div>
                <div class="form-group">
                    <label>Área</label>
                    <p id="detail_personal_area"></p>
                </div>
                <div class="form-group">
                    <label>Correo</label>
                    <p id="detail_personal_correo"></p>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <p id="detail_personal_telefono"></p>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <p id="detail_personal_estatus"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDetailsPersonal()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        const personalStoreUrl = "{{ route('admin.personal.store') }}";
        const personalBaseUrl = "{{ url('/personal') }}";

        function openModalPersonal() {
            document.getElementById('modalPersonal').classList.add('show');
            document.getElementById('formPersonal').reset();
            document.getElementById('modalPersonalTitle').textContent = 'Agregar personal';
            document.getElementById('formPersonal').action = personalStoreUrl;
            document.getElementById('modalPersonalMethod').value = 'POST';
            document.querySelector('.btn-submit').textContent = 'Guardar';
        }

        function closeModalPersonal() {
            document.getElementById('modalPersonal').classList.remove('show');
        }

        function editPersonal(button) {
            const personal = {
                id_personal: button.dataset.id_personal,
                nombre: button.dataset.nombre,
                apellido_paterno: button.dataset.apellido_paterno,
                apellido_materno: button.dataset.apellido_materno,
                puesto: button.dataset.puesto,
                correo: button.dataset.correo,
                telefono: button.dataset.telefono,
                id_area: button.dataset.id_area,
                estatus: button.dataset.estatus,
            };

            openModalPersonal();
            document.getElementById('modalPersonalTitle').textContent = 'Editar personal';
            document.getElementById('nombre').value = personal.nombre || '';
            document.getElementById('apellido_paterno').value = personal.apellido_paterno || '';
            document.getElementById('apellido_materno').value = personal.apellido_materno || '';
            document.getElementById('puesto').value = personal.puesto || '';
            document.getElementById('correo').value = personal.correo || '';
            document.getElementById('telefono').value = personal.telefono || '';
            document.getElementById('id_area').value = personal.id_area || '';
            document.getElementById('estatus').value = personal.estatus || 'Activo';
            document.getElementById('formPersonal').action = `${personalBaseUrl}/${personal.id_personal}`;
            document.getElementById('modalPersonalMethod').value = 'PUT';
            document.querySelector('.btn-submit').textContent = 'Guardar cambios';
        }

        function openDetailsPersonal(button) {
            document.getElementById('detail_personal_nombre').textContent = `${button.dataset.nombre} ${button.dataset.apellido_paterno} ${button.dataset.apellido_materno}`.trim();
            document.getElementById('detail_personal_puesto').textContent = button.dataset.puesto || 'N/A';
            document.getElementById('detail_personal_area').textContent = button.dataset.area_nombre || 'Sin área';
            document.getElementById('detail_personal_correo').textContent = button.dataset.correo || 'N/A';
            document.getElementById('detail_personal_telefono').textContent = button.dataset.telefono || 'N/A';
            document.getElementById('detail_personal_estatus').textContent = button.dataset.estatus || 'N/A';
            document.getElementById('modalPersonalDetails').classList.add('show');
        }

        function closeDetailsPersonal() {
            document.getElementById('modalPersonalDetails').classList.remove('show');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalPersonal');
            const modalDetails = document.getElementById('modalPersonalDetails');
            if (event.target === modal) {
                closeModalPersonal();
            }
            if (event.target === modalDetails) {
                closeDetailsPersonal();
            }
        }
    </script>
@endsection
