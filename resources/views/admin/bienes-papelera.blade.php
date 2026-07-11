@extends('layouts.admin')

@section('title', 'Papelera de Bienes')

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

    <div class="header">
        <div>
            <h1>Papelera</h1>
            <p>Bienes eliminados recientemente</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('admin.bienes') }}" class="btn-agregar"><i class="fa-solid fa-arrow-left"></i> Volver a bienes</a>
            <button type="button" class="btn-secundario" id="restoreSelectedBtn" onclick="restoreSelected()" disabled style="display:none;">
                <i class="fa-solid fa-trash-arrow-up"></i> Restaurar seleccionados
            </button>
            <button type="button" class="btn-secundario btn-danger" id="deleteSelectedBtn" onclick="permaDeleteSelected()" disabled style="display:none;">
                <i class="fa-solid fa-trash"></i> Eliminar permanentemente
            </button>
        </div>
    </div>

    <div class="page-actions-extra">
        <button type="button" class="btn-secundario" onclick="restoreAllBien()">
            <i class="fa-solid fa-trash-arrow-up"></i> Restaurar todos
        </button>
        <button type="button" class="btn-secundario btn-danger" onclick="permaDeleteAllBien()">
            <i class="fa-solid fa-trash"></i> Eliminar permanentemente todos
        </button>
    </div>

    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllCheckboxes(this)">
                    </th>
                    <th>No. Inventario</th>
                    <th>Nombre del bien</th>
                    <th>Marca</th>
                    <th>&Aacute;rea</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bienes as $bien)
                    <tr>
                        <td>
                            <input type="checkbox" class="bien-checkbox" value="{{ $bien->id_bien }}" onchange="updateButtons()">
                        </td>
                        <td>{{ $bien->no_inventario }}</td>
                        <td>{{ $bien->nombre_bien }}</td>
                        <td>{{ $bien->marcaRelacion?->nombre_marca ?? $bien->marca ?? 'N/A' }}</td>
                        <td>{{ $bien->area?->nombre_area ?? 'Sin área' }}</td>
                        <td><span class="estado {{ strtolower($bien->estatus) }}">{{ $bien->estatus }}</span></td>
                        <td>{{ $bien->personal?->nombre ?? 'Sin asignar' }}</td>
                        <td class="acciones">
                            <form method="POST" action="{{ route('admin.bienes.restaurar', $bien) }}" style="display:inline-flex;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="action-btn action-edit" title="Restaurar" aria-label="Restaurar">
                                    <i class="fa-solid fa-trash-arrow-up"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.bienes.force-destroy', $bien) }}" style="display:inline-flex;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="action-btn action-danger" title="Eliminar permanentemente" aria-label="Eliminar permanentemente" onclick="confirmThenSubmit(this, '¿Está seguro de eliminar permanentemente este bien? Esta acción no se puede deshacer.')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:20px;">La papelera est&aacute; vac&iacute;a</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="paginacion">
            <span>Mostrando {{ $bienes->firstItem() ?? 0 }} - {{ $bienes->lastItem() ?? 0 }} de {{ $bienes->total() }} bienes</span>
            @if($bienes->hasPages())
                <div class="paginas">
                    @if($bienes->onFirstPage())
                        <button disabled>&laquo;</button>
                    @else
                        <a href="{{ $bienes->previousPageUrl() }}"><button>&laquo;</button></a>
                    @endif
                    @foreach($bienes->getUrlRange(max(1, $bienes->currentPage() - 2), min($bienes->lastPage(), $bienes->currentPage() + 2)) as $page => $url)
                        <a href="{{ $url }}"><button class="{{ $page == $bienes->currentPage() ? 'pagina-activa' : '' }}">{{ $page }}</button></a>
                    @endforeach
                    @if($bienes->hasMorePages())
                        <a href="{{ $bienes->nextPageUrl() }}"><button>&raquo;</button></a>
                    @else
                        <button disabled>&raquo;</button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <form id="bulkRestoreForm" method="POST" action="{{ route('admin.bienes.bulk-restore') }}" style="display:none;">
        @csrf
        <input type="hidden" id="bulkRestoreIds" name="ids" value="">
    </form>

    <form id="bulkDeletePermanenteForm" method="POST" action="{{ route('admin.bienes.bulk-force-destroy') }}" style="display:none;">
        @csrf
        <input type="hidden" id="bulkDeletePermanenteIds" name="ids" value="">
    </form>

    <form id="restoreAllForm" method="POST" action="{{ route('admin.bienes.restore-all') }}" style="display:none;">
        @csrf
    </form>

    <form id="permaDeleteAllForm" method="POST" action="{{ route('admin.bienes.force-destroy-all') }}" style="display:none;">
        @csrf
    </form>

    <script>
        function toggleAllCheckboxes(source) {
            document.querySelectorAll('.bien-checkbox').forEach(function(cb) {
                cb.checked = source.checked;
            });
            updateButtons();
        }

        function updateButtons() {
            var checked = document.querySelectorAll('.bien-checkbox:checked');
            var count = checked.length;

            var restoreBtn = document.getElementById('restoreSelectedBtn');
            var deleteBtn = document.getElementById('deleteSelectedBtn');
            if (count > 0) {
                restoreBtn.disabled = false;
                restoreBtn.style.display = '';
                restoreBtn.innerHTML = '<i class="fa-solid fa-trash-arrow-up"></i> Restaurar (' + count + ')';
                deleteBtn.disabled = false;
                deleteBtn.style.display = '';
                deleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i> Eliminar permanentemente (' + count + ')';
            } else {
                restoreBtn.disabled = true;
                restoreBtn.style.display = 'none';
                deleteBtn.disabled = true;
                deleteBtn.style.display = 'none';
            }
        }

        function restoreSelected() {
            var checked = document.querySelectorAll('.bien-checkbox:checked');
            if (checked.length === 0) return;
            var ids = Array.from(checked).map(function(cb) { return cb.value; });
            showConfirm('Restaurar ' + checked.length + ' bien(es) seleccionados?', function () {
                document.getElementById('bulkRestoreIds').value = JSON.stringify(ids);
                document.getElementById('bulkRestoreForm').submit();
            });
        }

        function permaDeleteSelected() {
            var checked = document.querySelectorAll('.bien-checkbox:checked');
            if (checked.length === 0) return;
            var ids = Array.from(checked).map(function(cb) { return cb.value; });
            showConfirm('¿Está seguro de eliminar permanentemente ' + checked.length + ' bien(es)? Esta acción no se puede deshacer.', function () {
                document.getElementById('bulkDeletePermanenteIds').value = JSON.stringify(ids);
                document.getElementById('bulkDeletePermanenteForm').submit();
            });
        }

        function restoreAllBien() {
            var total = {{ $bienes->total() }};
            if (total === 0) {
                showAlert('No hay bienes en la papelera para restaurar.');
                return;
            }
            showConfirm('¿Está seguro de restaurar los ' + total + ' bien(es)? Se restaurarán todos sin importar los filtros o página actual.', function () {
                document.getElementById('restoreAllForm').submit();
            });
        }

        function permaDeleteAllBien() {
            var total = {{ $bienes->total() }};
            if (total === 0) {
                showAlert('No hay bienes en la papelera para eliminar.');
                return;
            }
            showConfirm('¿Está seguro de eliminar permanentemente los ' + total + ' bien(es)? Esta acción no se puede deshacer.', function () {
                document.getElementById('permaDeleteAllForm').submit();
            });
        }
    </script>
@endsection