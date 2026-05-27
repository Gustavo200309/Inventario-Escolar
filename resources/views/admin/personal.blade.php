@extends('layouts.admin')

@section('title', 'Gestion de Personal')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de Personal</h1>
            <p>Administra el personal y sus asignaciones</p>
        </div>

        <button type="button" class="btn-agregar">
            <i class="fa-solid fa-plus"></i>
            Agregar personal
        </button>
    </div>

    <div class="buscador">
        <div class="input-buscar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar por nombre, cargo o &aacute;rea...">
        </div>
    </div>

    <div class="personal-grid">
        <article class="card">
            <div class="card-top">
                <div class="avatar">
                    <i class="fa-solid fa-users"></i>
                </div>

                <span class="estado">Activo</span>
            </div>

            <h2 class="nombre">Juan P&eacute;rez</h2>
            <p class="puesto">Analista de Sistemas</p>
            <p class="area">Sistemas</p>

            <div class="linea"></div>

            <div class="datos">
                <div class="dato">
                    <span class="label">Correo</span>
                    <span class="valor">juan.perez@email.com</span>
                </div>

                <div class="dato">
                    <span class="label">Tel&eacute;fono</span>
                    <span class="valor">999 123 4567</span>
                </div>
            </div>

            <div class="botones">
                <button type="button" class="btn-ver">Ver perfil</button>
                <button type="button" class="btn-icon" aria-label="Editar"><i class="fa-solid fa-pen"></i></button>
                <button type="button" class="btn-icon btn-delete" aria-label="Eliminar"><i class="fa-solid fa-trash"></i></button>
            </div>
        </article>

        <article class="card">
            <div class="card-top">
                <div class="avatar">
                    <i class="fa-solid fa-users"></i>
                </div>

                <span class="estado">Activo</span>
            </div>

            <h2 class="nombre">Mar&iacute;a Garc&iacute;a</h2>
            <p class="puesto">Auxiliar Administrativo</p>
            <p class="area">Administraci&oacute;n</p>

            <div class="linea"></div>

            <div class="datos">
                <div class="dato">
                    <span class="label">Correo</span>
                    <span class="valor">maria.garcia@email.com</span>
                </div>

                <div class="dato">
                    <span class="label">Tel&eacute;fono</span>
                    <span class="valor">999 987 6543</span>
                </div>
            </div>

            <div class="botones">
                <button type="button" class="btn-ver">Ver perfil</button>
                <button type="button" class="btn-icon" aria-label="Editar"><i class="fa-solid fa-pen"></i></button>
                <button type="button" class="btn-icon btn-delete" aria-label="Eliminar"><i class="fa-solid fa-trash"></i></button>
            </div>
        </article>

        <article class="card">
            <div class="card-top">
                <div class="avatar">
                    <i class="fa-solid fa-users"></i>
                </div>

                <span class="estado">Activo</span>
            </div>

            <h2 class="nombre">Carlos L&oacute;pez</h2>
            <p class="puesto">Jefe de Recursos Humanos</p>
            <p class="area">Recursos Humanos</p>

            <div class="linea"></div>

            <div class="datos">
                <div class="dato">
                    <span class="label">Correo</span>
                    <span class="valor">carlos.lopez@email.com</span>
                </div>

                <div class="dato">
                    <span class="label">Tel&eacute;fono</span>
                    <span class="valor">999 654 3210</span>
                </div>
            </div>

            <div class="botones">
                <button type="button" class="btn-ver">Ver perfil</button>
                <button type="button" class="btn-icon" aria-label="Editar"><i class="fa-solid fa-pen"></i></button>
                <button type="button" class="btn-icon btn-delete" aria-label="Eliminar"><i class="fa-solid fa-trash"></i></button>
            </div>
        </article>
    </div>
@endsection
