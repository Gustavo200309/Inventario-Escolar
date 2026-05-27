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
                <span>1,247</span>
            </div>

            <div class="porcentaje">
                <i class="fa-solid fa-arrow-trend-up"></i>
                +12%
            </div>
        </article>

        <article class="tarjeta">
            <div class="icono oscuro">
                <i class="fa-solid fa-file-lines"></i>
            </div>

            <div class="info">
                <h3>Bienes Asignados</h3>
                <span>1,089</span>
            </div>

            <div class="porcentaje">
                <i class="fa-solid fa-arrow-trend-up"></i>
                +8%
            </div>
        </article>

        <article class="tarjeta">
            <div class="icono oscuro2">
                <i class="fa-solid fa-users"></i>
            </div>

            <div class="info">
                <h3>Personal Activo</h3>
                <span>156</span>
            </div>

            <div class="porcentaje">
                <i class="fa-solid fa-arrow-trend-up"></i>
                +3%
            </div>
        </article>

        <article class="tarjeta">
            <div class="icono verde">
                <i class="fa-solid fa-building"></i>
            </div>

            <div class="info">
                <h3>&Aacute;reas Registradas</h3>
                <span>24</span>
            </div>

            <div class="porcentaje">
                <i class="fa-solid fa-arrow-trend-up"></i>
                +5%
            </div>
        </article>
    </section>

    <section class="graficas">
        <article class="grafica">
            <h2>Bienes por Mes</h2>

            <div class="chart">
                <div class="barra-item">
                    <div class="barra h1"></div>
                    <span>Ene</span>
                </div>

                <div class="barra-item">
                    <div class="barra h2"></div>
                    <span>Feb</span>
                </div>

                <div class="barra-item">
                    <div class="barra h3"></div>
                    <span>Mar</span>
                </div>

                <div class="barra-item">
                    <div class="barra h4"></div>
                    <span>Abr</span>
                </div>

                <div class="barra-item">
                    <div class="barra h5"></div>
                    <span>May</span>
                </div>

                <div class="barra-item">
                    <div class="barra h6"></div>
                    <span>Jun</span>
                </div>
            </div>
        </article>

        <article class="grafica">
            <h2>Distribuci&oacute;n de Bienes</h2>

            <div class="pie-contenedor">
                <div class="pie-chart"></div>

                <div class="leyenda">
                    <div><span class="color asignado"></span> Asignados</div>
                    <div><span class="color mantenimiento"></span> Mantenimiento</div>
                    <div><span class="color baja"></span> Baja</div>
                    <div><span class="color disponible"></span> Disponibles</div>
                </div>
            </div>
        </article>
    </section>
@endsection
