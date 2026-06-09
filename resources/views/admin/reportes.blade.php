@extends('layouts.admin')

@section('title', 'Generacion de Reportes')

@section('content')
    <div class="header">
        <div>
            <h1>Generaci&oacute;n de Reportes</h1>
            <p>Genera y exporta reportes del sistema de inventario</p>
        </div>
    </div>

    @if(session('error'))
        <div class="component-alert component-alert-error" style="margin-bottom:20px;">
            <div class="component-alert-content">{{ session('error') }}</div>
        </div>
    @endif

    <div class="report-layout">
        <aside class="report-types">
            <div class="report-types-header">
                <div class="report-types-icon">
                    <i class="fa-solid fa-chart-simple"></i>
                </div>
                <div>
                    <h3>Tipos de reporte</h3>
                    <p>Selecciona un tipo</p>
                </div>
            </div>

            <a href="{{ route('admin.reportes', array_merge(request()->except('tipo'), ['tipo' => 'inventario'])) }}" class="report-card {{ ($filters['tipo'] ?? 'inventario') === 'inventario' ? 'active' : '' }}">
                <div class="report-card-header">
                    <div class="report-card-icon primary">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <div>
                        <h4>Inventario General</h4>
                        <p>Reporte completo de todos los bienes registrados</p>
                    </div>
                </div>
                <div class="report-card-footer">
                    <span class="component-badge component-badge-success">{{ $totalBienes }} bienes</span>
                </div>
            </a>

            <a href="{{ route('admin.reportes', array_merge(request()->except('tipo'), ['tipo' => 'pendientes'])) }}" class="report-card {{ ($filters['tipo'] ?? '') === 'pendientes' ? 'active' : '' }}">
                <div class="report-card-header">
                    <div class="report-card-icon warning">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <div>
                        <h4>Bienes Pendientes</h4>
                        <p>Listado de bienes sin asignar o pendientes</p>
                    </div>
                </div>
                <div class="report-card-footer">
                    <span class="component-badge component-badge-warning">{{ $bienes->whereIn('estatus', ['Pendiente', 'En revision', 'En mantenimiento', 'Danado'])->count() }} pendientes</span>
                </div>
            </a>
        </aside>

        <section class="config">
            <h3>Configuraci&oacute;n del reporte</h3>

            <form method="GET">
                <input type="hidden" name="tipo" value="{{ $filters['tipo'] ?? 'inventario' }}">

                <div class="grid">
                    <div class="form-group">
                        <label for="id_area">Filtrar por &aacute;rea</label>
                        <select id="id_area" name="id_area">
                            <option value="">Todas las &aacute;reas</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id_area }}" {{ (string) ($filters['id_area'] ?? '') === (string) $area->id_area ? 'selected' : '' }}>
                                    {{ $area->nombre_area }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_personal">Filtrar por responsable</label>
                        <select id="id_personal" name="id_personal">
                            <option value="">Todos los responsables</option>
                            @foreach($personals as $personal)
                                <option value="{{ $personal->id_personal }}" {{ (string) ($filters['id_personal'] ?? '') === (string) $personal->id_personal ? 'selected' : '' }}>
                                    {{ $personal->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label for="estatus">Filtrar por estado</label>
                        <select id="estatus" name="estatus">
                            <option value="">Todos los estados</option>
                            @foreach($estatuses as $estado)
                                <option value="{{ $estado }}" {{ ($filters['estatus'] ?? '') === $estado ? 'selected' : '' }}>
                                    {{ $estado }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha_inicio">Fecha inicial</label>
                        <input id="fecha_inicio" name="fecha_inicio" type="date" value="{{ $filters['fecha_inicio'] ?? '' }}">
                    </div>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label for="fecha_fin">Fecha final</label>
                        <input id="fecha_fin" name="fecha_fin" type="date" value="{{ $filters['fecha_fin'] ?? '' }}">
                    </div>

                    <div class="form-group" style="align-self: end;">
                        <button type="submit" class="btn-secundario">
                            <i class="fa-solid fa-filter"></i>
                            Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>

            <div class="separator"></div>

            <div class="stats">
                <article class="stat-card">
                    <h3>Total bienes</h3>
                    <span class="green">{{ $totalBienes }}</span>
                </article>

                <article class="stat-card">
                    <h3>Valor total</h3>
                    <span class="green">${{ number_format($valorTotal, 2) }}</span>
                </article>

                <article class="stat-card">
                    <h3>Estados</h3>
                    <span class="green">{{ $porEstado->count() }}</span>
                </article>
            </div>

            <div class="preview">
                <h4>Vista previa</h4>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No. inventario</th>
                                <th>Bien</th>
                                <th>Estado</th>
                                <th>&Aacute;rea</th>
                                <th>Responsable</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bienes->take(25) as $bien)
                                <tr>
                                    <td>{{ $bien->no_inventario }}</td>
                                    <td>{{ $bien->nombre_bien }}</td>
                                    <td><span class="estado {{ strtolower($bien->estatus) }}">{{ $bien->estatus }}</span></td>
                                    <td>{{ $bien->area?->nombre_area ?? 'Sin area' }}</td>
                                    <td>{{ $bien->personal?->nombre ?? 'Sin responsable' }}</td>
                                    <td>${{ number_format((float) ($bien->valor ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px;">No hay bienes para los filtros seleccionados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="export">
                <h3>Exportar reporte</h3>

                <div class="export-buttons">
                    <a href="{{ route('admin.reportes.export', array_merge(['format' => 'pdf'], request()->query())) }}" class="export-btn pdf">
                        <i class="fa-solid fa-file-pdf"></i>
                        Generar PDF
                    </a>

                    <a href="{{ route('admin.reportes.export', array_merge(['format' => 'xlsx'], request()->query())) }}" class="export-btn excel">
                        <i class="fa-solid fa-file-excel"></i>
                        Exportar Excel
                    </a>

                    <a href="{{ route('admin.reportes.export', array_merge(['format' => 'csv'], request()->query())) }}" class="export-btn csv">
                        <i class="fa-solid fa-file-csv"></i>
                        Exportar CSV
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
