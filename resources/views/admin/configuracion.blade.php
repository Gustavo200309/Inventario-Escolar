@extends('layouts.admin')

@section('title', 'Configuracion del Sistema')

@section('content')
    <div class="header">
        <div>
            <h1>Configuraci&oacute;n del Sistema</h1>
            <p>Administra la configuraci&oacute;n general del sistema</p>
        </div>
    </div>

    <div class="cards">
        <article class="card theme-card">
            <div class="card-top">
                <div class="icon-box">
                    <i class="fa-solid fa-moon"></i>
                </div>

                <h3>Apariencia</h3>
            </div>

            <p>Selecciona la paleta visual del panel administrativo.</p>

            <div class="theme-setting">
                <div>
                    <strong>Modo oscuro</strong>
                    <span data-theme-label>Modo claro activado</span>
                </div>

                <label class="theme-switch" aria-label="Activar modo oscuro">
                    <input type="checkbox" data-theme-toggle>
                    <span class="theme-slider">
                        <i class="fa-solid fa-sun"></i>
                        <i class="fa-solid fa-moon"></i>
                    </span>
                </label>
            </div>
        </article>

        <article class="card">
            <div class="card-top">
                <div class="icon-box">
                    <i class="fa-solid fa-users"></i>
                </div>

                <h3>Gesti&oacute;n de Usuarios</h3>
            </div>

            <p>Administra los usuarios del sistema y sus roles</p>

            <div class="buttons">
                <button type="button" class="btn btn-green">Administrar usuarios</button>
            </div>
        </article>

        <article class="card">
            <div class="card-top">
                <div class="icon-box">
                    <i class="fa-solid fa-database"></i>
                </div>

                <h3>Respaldo de Base de Datos</h3>
            </div>

            <p>Genera respaldos de la base de datos del sistema</p>

            <div class="buttons">
                <button type="button" class="btn btn-green">Generar respaldo</button>
                <button type="button" class="btn btn-light">Ver respaldos anteriores</button>
            </div>
        </article>

        <article class="card">
            <div class="card-top">
                <div class="icon-box">
                    <i class="fa-solid fa-print"></i>
                </div>

                <h3>Configuraci&oacute;n de Impresoras</h3>
            </div>

            <p>Configura las impresoras para etiquetas de bienes</p>

            <div class="printer-box">
                <label>Impresora predeterminada</label>

                <select>
                    <option>HP LaserJet Pro</option>
                    <option>Epson L3250</option>
                    <option>Canon PIXMA</option>
                </select>
            </div>
        </article>
    </div>
@endsection
