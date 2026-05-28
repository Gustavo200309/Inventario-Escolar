@extends('layouts.admin')

@section('title', 'Generacion de Reportes')

@section('content')
    <div class="header">
        <div>
            <h1>Generaci&oacute;n de Reportes</h1>
            <p>Genera y exporta reportes del sistema de inventario</p>
        </div>
    </div>

    <div class="report-layout">
        <aside class="report-types">
            <h3>Tipos de reporte</h3>

            <div class="report-card active">
                <h4><i class="fa-solid fa-file-lines"></i> Inventario General</h4>
                <p>Reporte completo de todos los bienes registrados</p>
            </div>

            <div class="report-card">
                <h4><i class="fa-solid fa-file-contract"></i> Resguardos</h4>
                <p>Documento de resguardo de bienes por persona</p>
            </div>

            <div class="report-card active">
                <h4><i class="fa-solid fa-building"></i> Bienes por &Aacute;rea</h4>
                <p>Distribuci&oacute;n de bienes por &aacute;rea institucional</p>
            </div>

            <div class="report-card">
                <h4><i class="fa-solid fa-users"></i> Bienes por Personal</h4>
                <p>Bienes asignados a cada miembro del personal</p>
            </div>

            <div class="report-card">
                <h4><i class="fa-solid fa-circle-exclamation"></i> Bienes Pendientes</h4>
                <p>Listado de bienes sin asignar o pendientes</p>
            </div>
        </aside>

        <section class="config">
            <h3>Configuraci&oacute;n del reporte</h3>

            <div class="form-group">
                <label>Tipo de reporte</label>
                <input type="text" value="Inventario General">
            </div>

            <div class="grid">
                <div class="form-group">
                    <label>Filtrar por &aacute;rea</label>

                    <select>
                        <option>Todas las &aacute;reas</option>
                        <option>Sistemas</option>
                        <option>Administraci&oacute;n</option>
                        <option>Recursos Humanos</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Filtrar por estado</label>

                    <select>
                        <option>Todos los estados</option>
                        <option>Activo</option>
                        <option>Pendiente</option>
                        <option>Baja</option>
                    </select>
                </div>
            </div>

            <div class="grid">
                <div class="form-group">
                    <label>Fecha inicial</label>
                    <input type="text" placeholder="dd/mm/aaaa">
                </div>

                <div class="form-group">
                    <label>Fecha final</label>
                    <input type="text" placeholder="dd/mm/aaaa">
                </div>
            </div>

            <div class="separator"></div>

            <div class="preview">
                <h4>Vista previa</h4>

                <div class="preview-box">
                    <div class="preview-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>

                    <h5>Vista previa del reporte</h5>
                    <p>Configura los filtros y genera el reporte para ver el contenido</p>
                </div>
            </div>

            <div class="export">
                <h3>Exportar reporte</h3>

                <div class="export-buttons">
                    <button type="button" class="export-btn pdf">
                        <i class="fa-solid fa-file-pdf"></i>
                        Generar PDF
                    </button>

                    <button type="button" class="export-btn excel">
                        <i class="fa-solid fa-file-excel"></i>
                        Exportar Excel
                    </button>

                    <button type="button" class="export-btn print">
                        <i class="fa-solid fa-print"></i>
                        Imprimir
                    </button>
                </div>
            </div>
        </section>
    </div>
@endsection
