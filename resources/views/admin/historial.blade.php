@extends('layouts.admin')

@section('title', 'Historial de Movimientos')

@section('content')
    <div class="header">
        <div>
            <h1>Historial de Movimientos</h1>
            <p>Registro completo de todas las operaciones del sistema, agrupado por tipo de movimiento</p>
        </div>
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

    <div class="buscador">
        <form method="GET" class="buscar-form" style="flex-direction:column;align-items:stretch;gap:14px;">
            <div class="input-buscar" style="flex:none;width:100%;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar por bien, responsable, area u observaciones..." value="{{ $search ?? '' }}">
            </div>

            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
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

                <button type="submit" class="btn-secundario">
                    <i class="fa-solid fa-filter"></i>
                    Filtrar
                </button>

                <a href="{{ route('admin.historial') }}" class="btn-secundario">
                    <i class="fa-solid fa-rotate-left"></i>
                    Limpiar
                </a>

                <a href="{{ route('admin.historial.export', array_merge(['format' => 'csv'], request()->query())) }}" class="btn-secundario">
                    <i class="fa-solid fa-file-csv"></i>
                    CSV
                </a>

                <a href="{{ route('admin.historial.export', array_merge(['format' => 'pdf'], request()->query())) }}" class="btn-secundario">
                    <i class="fa-solid fa-file-pdf"></i>
                    PDF
                </a>
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
        .historial-group {
            margin-bottom: 28px;
        }
        .historial-group-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
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
    </style>
@endsection
