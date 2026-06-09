@extends('layouts.admin')

@section('title', 'Historial de Movimientos')

@section('content')
    <div class="header">
        <div>
            <h1>Historial de Movimientos</h1>
            <p>Registro completo de todas las operaciones del sistema</p>
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

    <div class="filters">
        <form method="GET" class="search" style="display: contents;">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Buscar en el historial..." value="{{ $search ?? '' }}">
            </div>

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

            <button type="submit" class="btn">
                <i class="fa-solid fa-filter"></i>
                Filtrar
            </button>

            <a href="{{ route('admin.historial') }}" class="btn">
                <i class="fa-solid fa-rotate-left"></i>
                Limpiar
            </a>
        </form>

        <a href="{{ route('admin.historial.export', array_merge(['format' => 'csv'], request()->query())) }}" class="btn">
            <i class="fa-solid fa-file-csv"></i>
            CSV
        </a>

        <a href="{{ route('admin.historial.export', array_merge(['format' => 'pdf'], request()->query())) }}" class="btn">
            <i class="fa-solid fa-file-pdf"></i>
            PDF
        </a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
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
                @forelse($historiales as $historial)
                    @php($tagClass = \Illuminate\Support\Str::slug($historial->tipo_movimiento))
                    <tr>
                        <td><span class="tag {{ $tagClass }}">{{ $historial->tipo_movimiento }}</span></td>
                        <td>{{ $historial->fecha_movimiento?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                        <td>{{ $historial->bien?->nombre_bien ?? 'Sin bien' }}</td>
                        <td>{{ $historial->personalAnterior?->nombre ?? '-' }}</td>
                        <td>{{ $historial->personalNuevo?->nombre ?? '-' }}</td>
                        <td>{{ $historial->areaAnterior?->nombre_area ?? '-' }}</td>
                        <td>{{ $historial->areaNueva?->nombre_area ?? '-' }}</td>
                        <td>{{ $historial->observaciones ?? 'Sin observaciones' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">No hay movimientos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
