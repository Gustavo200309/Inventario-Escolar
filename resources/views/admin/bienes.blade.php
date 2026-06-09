@extends('layouts.admin')

@section('title', 'Gestion de Bienes')

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
            <h1>Gesti&oacute;n de Bienes</h1>
            <p>Administra inventario de bienes institucionales</p>
        </div>

        @if(Auth::user()->isAdmin())
            <button type="button" class="btn-agregar" onclick="openModalBien()">
                <i class="fa-solid fa-plus"></i>
                Agregar bien
            </button>
        @endif
    </div>

    <div class="buscador">
        <form method="GET" class="buscar-form">
            <div class="input-buscar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por nombre, serie o n&uacute;mero de inventario" value="{{ $search ?? '' }}">
            </div>

            <select name="estatus">
                <option value="">Todos los estados</option>
                <option value="Asignado" {{ ($estatus ?? '') === 'Asignado' ? 'selected' : '' }}>Asignado</option>
                <option value="Disponible" {{ ($estatus ?? '') === 'Disponible' ? 'selected' : '' }}>Disponible</option>
                <option value="Pendiente" {{ ($estatus ?? '') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
            </select>

            <button type="submit" class="btn-secundario"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </form>
        
        @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.reportes.export', 'excel') }}" class="btn-secundario"><i class="fa-solid fa-file-export"></i> Exportar</a>
        @endif
    </div>

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr>
                    <th>No. Inventario</th>
                    <th>ID SEP</th>
                    <th>Nombre del bien</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse($bienes as $bien)
                    <tr>
                        <td>{{ $bien->no_inventario }}</td>
                        <td>{{ $bien->id_sep ?? 'N/A' }}</td>
                        <td>{{ $bien->nombre_bien }}</td>
                        <td>{{ $bien->marca ?? 'N/A' }}</td>
                        <td>{{ $bien->modelo ?? 'N/A' }}</td>
                        <td>${{ number_format($bien->valor ?? 0, 2) }}</td>
                        <td><span class="estado {{ strtolower($bien->estatus) }}">{{ $bien->estatus }}</span></td>
                        <td>{{ $bien->personal?->nombre ?? 'Sin asignar' }}</td>
                        <td class="acciones">
                            <button type="button" class="action-btn" title="Ver" onclick="openDetailsBien(this)"
                                data-id_bien="{{ $bien->id_bien }}"
                                data-no_inventario="{{ $bien->no_inventario }}"
                                data-id_sep="{{ $bien->id_sep }}"
                                data-nombre_bien="{{ $bien->nombre_bien }}"
                                data-marca="{{ $bien->marca }}"
                                data-modelo="{{ $bien->modelo }}"
                                data-serie="{{ $bien->serie }}"
                                data-valor="{{ $bien->valor }}"
                                data-id_area="{{ $bien->id_area }}"
                                data-area_nombre="{{ $bien->area?->nombre_area }}"
                                data-id_personal="{{ $bien->id_personal }}"
                                data-personal_nombre="{{ $bien->personal?->nombre }}"
                                data-estatus="{{ $bien->estatus }}"
                            ><i class="fa-solid fa-eye"></i></button>
                            @if(Auth::user()->isAdmin())
                                <button type="button" class="action-btn" title="Editar" onclick="editBien(this)"
                                    data-id_bien="{{ $bien->id_bien }}"
                                    data-no_inventario="{{ $bien->no_inventario }}"
                                    data-id_sep="{{ $bien->id_sep }}"
                                    data-nombre_bien="{{ $bien->nombre_bien }}"
                                    data-marca="{{ $bien->marca }}"
                                    data-modelo="{{ $bien->modelo }}"
                                    data-serie="{{ $bien->serie }}"
                                    data-valor="{{ $bien->valor }}"
                                    data-id_area="{{ $bien->id_area }}"
                                    data-id_personal="{{ $bien->id_personal }}"
                                    data-estatus="{{ $bien->estatus }}"
                                ><i class="fa-solid fa-pen"></i></button>
                                <form method="POST" action="{{ route('admin.bienes.destroy', $bien) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Eliminar" onclick="return confirm('¿Está seguro?')" style="background:none;border:none;cursor:pointer;color:#dc3545;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:20px;">No hay bienes registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="paginacion">
            <span>Mostrando {{ $bienes->count() }} bienes</span>
        </div>
    </div>

    <!-- Modal Agregar/Editar Bien -->
    <div id="modalBien" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalBienTitle">Agregar bien</h2>
                <button onclick="closeModalBien()">&times;</button>
            </div>
            <form id="formBien" method="POST" action="{{ route('admin.bienes.store') }}">
                @csrf
                <input type="hidden" name="_method" id="modalBienMethod" value="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="no_inventario">No. Inventario *</label>
                        <input type="text" id="no_inventario" name="no_inventario" required>
                    </div>
                    <div class="form-group">
                        <label for="id_sep">ID SEP</label>
                        <input type="text" id="id_sep" name="id_sep">
                    </div>
                    <div class="form-group">
                        <label for="nombre_bien">Nombre del bien *</label>
                        <input type="text" id="nombre_bien" name="nombre_bien" required>
                    </div>
                    <div class="form-group">
                        <label for="marca">Marca</label>
                        <input type="text" id="marca" name="marca">
                    </div>
                    <div class="form-group">
                        <label for="modelo">Modelo</label>
                        <input type="text" id="modelo" name="modelo">
                    </div>
                    <div class="form-group">
                        <label for="serie">Serie</label>
                        <input type="text" id="serie" name="serie">
                    </div>
                    <div class="form-group">
                        <label for="valor">Valor</label>
                        <input type="number" id="valor" name="valor" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="id_area">Área</label>
                        <select id="id_area" name="id_area">
                            <option value="">Seleccionar área</option>
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
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModalBien()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Detalles Bien -->
    <div id="modalBienDetails" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalles del bien</h2>
                <button onclick="closeDetailsBien()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>No. Inventario</label>
                    <p id="detail_no_inventario"></p>
                </div>
                <div class="form-group">
                    <label>ID SEP</label>
                    <p id="detail_id_sep"></p>
                </div>
                <div class="form-group">
                    <label>Nombre del bien</label>
                    <p id="detail_nombre_bien"></p>
                </div>
                <div class="form-group">
                    <label>Marca</label>
                    <p id="detail_marca"></p>
                </div>
                <div class="form-group">
                    <label>Modelo</label>
                    <p id="detail_modelo"></p>
                </div>
                <div class="form-group">
                    <label>Serie</label>
                    <p id="detail_serie"></p>
                </div>
                <div class="form-group">
                    <label>Valor</label>
                    <p id="detail_valor"></p>
                </div>
                <div class="form-group">
                    <label>Área</label>
                    <p id="detail_area_nombre"></p>
                </div>
                <div class="form-group">
                    <label>Responsable</label>
                    <p id="detail_personal_nombre"></p>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <p id="detail_estatus"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDetailsBien()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        function openModalBien() {
            document.getElementById('modalBien').classList.add('show');
            document.getElementById('formBien').action = '{{ route("admin.bienes.store") }}';
            document.getElementById('modalBienMethod').value = 'POST';
            document.getElementById('formBien').reset();
            document.getElementById('modalBienTitle').textContent = 'Agregar bien';
            document.querySelector('.btn-submit').textContent = 'Guardar';
        }

        function closeModalBien() {
            document.getElementById('modalBien').classList.remove('show');
        }

        function openDetailsBien(button) {
            document.getElementById('detail_no_inventario').textContent = button.dataset.no_inventario || 'N/A';
            document.getElementById('detail_id_sep').textContent = button.dataset.id_sep || 'N/A';
            document.getElementById('detail_nombre_bien').textContent = button.dataset.nombre_bien || 'N/A';
            document.getElementById('detail_marca').textContent = button.dataset.marca || 'N/A';
            document.getElementById('detail_modelo').textContent = button.dataset.modelo || 'N/A';
            document.getElementById('detail_serie').textContent = button.dataset.serie || 'N/A';
            document.getElementById('detail_valor').textContent = button.dataset.valor ? `$${parseFloat(button.dataset.valor).toFixed(2)}` : 'N/A';
            document.getElementById('detail_area_nombre').textContent = button.dataset.area_nombre || 'Sin área';
            document.getElementById('detail_personal_nombre').textContent = button.dataset.personal_nombre || 'Sin asignar';
            document.getElementById('detail_estatus').textContent = button.dataset.estatus || 'N/A';
            document.getElementById('modalBienDetails').classList.add('show');
        }

        function closeDetailsBien() {
            document.getElementById('modalBienDetails').classList.remove('show');
        }

        function editBien(button) {
            document.getElementById('modalBien').classList.add('show');
            document.getElementById('formBien').action = '{{ url('/bienes') }}/' + button.dataset.id_bien;
            document.getElementById('modalBienMethod').value = 'PUT';
            document.getElementById('formBien').reset();
            document.getElementById('modalBienTitle').textContent = 'Editar bien';
            document.querySelector('.btn-submit').textContent = 'Guardar cambios';
            document.getElementById('no_inventario').value = button.dataset.no_inventario || '';
            document.getElementById('id_sep').value = button.dataset.id_sep || '';
            document.getElementById('nombre_bien').value = button.dataset.nombre_bien || '';
            document.getElementById('marca').value = button.dataset.marca || '';
            document.getElementById('modelo').value = button.dataset.modelo || '';
            document.getElementById('serie').value = button.dataset.serie || '';
            document.getElementById('valor').value = button.dataset.valor || '';
            document.getElementById('id_area').value = button.dataset.id_area || '';
            document.getElementById('id_personal').value = button.dataset.id_personal || '';
            document.getElementById('estatus').value = button.dataset.estatus || 'Disponible';
        }

        window.onclick = function(event) {
            const modalBien = document.getElementById('modalBien');
            const modalDetails = document.getElementById('modalBienDetails');
            if (event.target === modalBien) {
                closeModalBien();
            }
            if (event.target === modalDetails) {
                closeDetailsBien();
            }
        }
    </script>
@endsection
