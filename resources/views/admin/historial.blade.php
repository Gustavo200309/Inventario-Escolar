@extends('layouts.admin')

@section('title', 'Historial de Movimientos')

@section('content')
    <div class="header historial-page-header">
        <div>
            <h1>Historial de Movimientos</h1>
            <p>Registro completo de todas las operaciones del sistema, agrupado por tipo de movimiento</p>
        </div>

        @include('admin.partials.header-logos')
    </div>

    @if(session('success'))
        <div class="component-alert component-alert-success" style="margin-bottom:20px;">
            <div class="component-alert-content">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="component-alert component-alert-error" style="margin-bottom:20px;">
            <div class="component-alert-content">{{ session('error') }}</div>
        </div>
    @endif

    <div class="buscador historial-filters">
        <form method="GET" class="buscar-form historial-search-form">
            <div class="input-buscar historial-search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por bien, responsable, area u observaciones..." value="{{ $search ?? '' }}">
            </div>

            <div class="historial-filter-row">
                <div class="historial-filter-fields">
                    <select name="tipo">
                        <option value="">Todos los tipos</option>
                        @foreach($tipos ?? [] as $tipoMovimiento)
                            <option value="{{ $tipoMovimiento }}" {{ ($tipo ?? '') === $tipoMovimiento ? 'selected' : '' }}>
                                {{ $tipoMovimiento }}
                            </option>
                        @endforeach
                    </select>

                    <input type="date" name="fecha_inicio" value="{{ $fechaInicio ?? '' }}" aria-label="Fecha inicial">
                    <input type="date" name="fecha_fin" value="{{ $fechaFin ?? '' }}" aria-label="Fecha final">
                </div>

                <div class="historial-filter-actions">
                    <button type="submit" class="btn-secundario">
                        <i class="fa-solid fa-filter"></i>
                        Filtrar
                    </button>

                    <a href="{{ route('admin.historial') }}" class="btn-secundario">
                        <i class="fa-solid fa-rotate-left"></i>
                        Limpiar
                    </a>
                </div>

                <div class="historial-export-actions" aria-label="Exportar historial">
                    <a href="{{ route('admin.historial.export', array_merge(['format' => 'csv'], request()->query())) }}" class="btn-secundario">
                        <i class="fa-solid fa-file-csv"></i>
                        CSV
                    </a>

                    <a href="{{ route('admin.historial.export', array_merge(['format' => 'pdf'], request()->query())) }}" class="btn-secundario">
                        <i class="fa-solid fa-file-pdf"></i>
                        PDF
                    </a>
                </div>
            </div>
        </form>
    </div>

    @forelse($historiales as $tipo => $items)
        <section class="historial-group">
            <div class="historial-group-header">
                <span class="tag {{ \Illuminate\Support\Str::slug($tipo) }}">{{ $tipo }}</span>
                <span class="historial-group-count">{{ $items->count() }} registro(s)</span>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Bien</th>
                            <th>Responsable anterior</th>
                            <th>Responsable nuevo</th>
                            <th>&Aacute;rea anterior</th>
                            <th>&Aacute;rea nueva</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($items as $historial)
                            <tr>
                                <td>{{ $historial->fecha_movimiento?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                                <td>
                                    {{ $historial->bien?->nombre_bien ?? 'Sin bien' }}
                                    @if($historial->bien?->no_inventario)
                                        <div class="historial-bien-codigo">{{ $historial->bien->no_inventario }}</div>
                                    @endif
                                </td>
                                <td>{{ $historial->personalAnterior?->nombre_completo ?? '-' }}</td>
                                <td>{{ $historial->personalNuevo?->nombre_completo ?? '-' }}</td>
                                <td>{{ $historial->areaAnterior?->nombre_area ?? '-' }}</td>
                                <td>{{ $historial->areaNueva?->nombre_area ?? '-' }}</td>
                                <td>{{ $historial->observaciones ?? 'Sin observaciones' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="table-container">
            <table>
                <tbody>
                    <tr>
                        <td style="text-align: center; padding: 20px;">No hay movimientos registrados</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforelse

    <div style="margin-top:18px;">
        @include('admin.partials.pagination', ['paginator' => $historialesPaginator])
    </div>

    <style>
        .historial-page-header .header-logos {
            flex: 0 0 auto;
        }
        .historial-filters {
            align-items: stretch;
        }
        .historial-search-form {
            display: grid;
            gap: 16px;
            align-items: stretch;
            width: 100%;
        }
        .historial-search-field {
            flex: none;
            width: 100%;
        }
        .historial-filter-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 14px;
        }
        .historial-filter-fields {
            display: grid;
            grid-template-columns: minmax(190px, 1fr) repeat(2, minmax(160px, 180px));
            gap: 14px;
            min-width: 0;
        }
        .historial-filter-actions,
        .historial-export-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .historial-export-actions {
            justify-content: flex-end;
            padding-left: 6px;
            border-left: 1px solid var(--border);
        }
        .historial-group {
            margin-bottom: 18px;
        }
        .historial-group-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .historial-group-count {
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }
        .historial-bien-codigo {
            color: var(--muted);
            font-size: 12px;
            margin-top: 2px;
        }
        .buscador input[type="date"] {
            min-height: 46px;
            padding: 0 20px;
            border-radius: 12px;
        }
        .historial-group table {
            min-width: 1120px;
            table-layout: fixed;
            font-size: 13px;
        }
        .historial-group th:nth-child(1) {
            width: 120px;
        }
        .historial-group th:nth-child(2) {
            width: 360px;
        }
        .historial-group th:nth-child(3),
        .historial-group th:nth-child(4),
        .historial-group th:nth-child(5),
        .historial-group th:nth-child(6) {
            width: 130px;
        }
        .historial-group th:nth-child(7) {
            width: 160px;
        }
        .historial-group thead th {
            padding: 10px 14px;
            font-size: 13px;
        }
        .historial-group tbody td {
            padding: 9px 14px;
            font-size: 13px;
            line-height: 1.45;
            vertical-align: top;
            overflow-wrap: anywhere;
        }
        @media (max-width: 1300px) {
            .historial-filter-row {
                grid-template-columns: 1fr;
                align-items: stretch;
            }
            .historial-export-actions {
                justify-content: flex-start;
                padding-left: 0;
                border-left: 0;
            }
        }
        @media (max-width: 720px) {
            .historial-filter-fields {
                grid-template-columns: 1fr;
            }
            .historial-filter-actions,
            .historial-export-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
@endsection
