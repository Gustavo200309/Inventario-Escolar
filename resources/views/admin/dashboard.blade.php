@extends('layouts.admin')

@section('title', 'Dashboard Inventario')

@section('content')
    <div class="header">
        <div>
            <h1>Dashboard</h1>
            <p>Resumen general del sistema de inventario</p>
        </div>
    </div>

    <section class="tarjetas">
        <article class="tarjeta">
            <div class="icono verde">
                <i class="fa-solid fa-cube"></i>
            </div>
            <div class="info">
                <h3>Total de Bienes</h3>
                <span>{{ number_format($totalBienes) }}</span>
            </div>
            <div class="porcentaje">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
        </article>

        <article class="tarjeta">
            <div class="icono oscuro">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <div class="info">
                <h3>Bienes Asignados</h3>
                <span>{{ number_format($bienesAsignados) }}</span>
            </div>
            <div class="porcentaje">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
        </article>

        <article class="tarjeta">
            <div class="icono oscuro2">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="info">
                <h3>Personal Activo</h3>
                <span>{{ number_format($personalActivo) }}</span>
            </div>
            <div class="porcentaje">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
        </article>

        <article class="tarjeta">
            <div class="icono verde">
                <i class="fa-solid fa-building"></i>
            </div>
            <div class="info">
                <h3>&Aacute;reas Registradas</h3>
                <span>{{ number_format($areasRegistradas) }}</span>
            </div>
            <div class="porcentaje">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
        </article>
    </section>

    <section class="graficas">
        <article class="grafica">
            <h2>Bienes por Mes</h2>
            <div style="position:relative;min-height:300px;">
                <canvas id="chartBienesMes"></canvas>
            </div>
        </article>

        <article class="grafica">
            <h2>Distribuci&oacute;n de Bienes</h2>
            <div style="position:relative;min-height:300px;display:flex;align-items:center;justify-content:center;">
                <div style="max-width:300px;width:100%;">
                    <canvas id="chartDistribucion"></canvas>
                </div>
            </div>
        </article>
    </section>

    @if($movimientosRecientes->isNotEmpty())
        <section class="grafica" style="margin-top:24px;">
            <h2>Movimientos Recientes</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Bien</th>
                            <th>Tipo</th>
                            <th>Anterior</th>
                            <th>Nuevo</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movimientosRecientes as $mov)
                            <tr>
                                <td>{{ $mov->bien?->nombre_bien ?? 'N/A' }}</td>
                                <td><span class="tag {{ str_replace(' ', '', $mov->tipo_movimiento ?? 'modificacion') }}">{{ $mov->tipo_movimiento ?? 'N/A' }}</span></td>
                                <td>{{ $mov->personalAnterior?->nombre ?? $mov->areaAnterior?->nombre_area ?? 'N/A' }}</td>
                                <td>{{ $mov->personalNuevo?->nombre ?? $mov->areaNueva?->nombre_area ?? 'N/A' }}</td>
                                <td>{{ $mov->fecha_movimiento ? \Carbon\Carbon::parse($mov->fecha_movimiento)->format('d/m/Y H:i') : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var chartBienesMes = document.getElementById('chartBienesMes');
            if (chartBienesMes) {
                new Chart(chartBienesMes, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($bienesPorMes->pluck('label')) !!},
                        datasets: [{
                            label: 'Bienes registrados',
                            data: {!! json_encode($bienesPorMes->pluck('total')) !!},
                            backgroundColor: 'rgba(47,148,60,0.75)',
                            borderColor: 'rgba(47,148,60,1)',
                            borderWidth: 2,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }

            var chartDistribucion = document.getElementById('chartDistribucion');
            if (chartDistribucion) {
                new Chart(chartDistribucion, {
                    type: 'doughnut',
                    data: {
                        labels: ['Asignados', 'Disponibles', 'Pendientes', 'Baja'],
                        datasets: [{
                            data: [{{ $bienesAsignados }}, {{ $bienesDisponibles }}, {{ $bienesPendientes }}, {{ $bienesBaja }}],
                            backgroundColor: ['#31923c', '#446d4c', '#b45309', '#9d3f3f'],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { padding: 16, usePointStyle: true }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endsection
