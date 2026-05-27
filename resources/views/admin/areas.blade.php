@extends('layouts.admin')

@section('title', 'Gestion de Areas')

@section('content')
    <div class="header">
        <div>
            <h1>Gesti&oacute;n de &Aacute;reas</h1>
            <p>Administra las &aacute;reas institucionales</p>
        </div>

        <button type="button" class="btn-agregar">
            <i class="fa-solid fa-plus"></i>
            Agregar &aacute;rea
        </button>
    </div>

    <div class="buscador">
        <div class="input-buscar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar por nombre de &aacute;rea o responsable...">
        </div>
    </div>

    <div class="cards">
        <article class="card">
            <div class="card-top">
                <div class="area-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <div class="card-actions">
                    <button type="button" class="action-btn" aria-label="Editar"><i class="fa-solid fa-pen"></i></button>
                    <button type="button" class="action-btn action-danger" aria-label="Eliminar"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>

            <h3>Sistemas</h3>
            <p class="responsable">Responsable: Juan P&eacute;rez</p>

            <div class="linea"></div>

            <div class="datos">
                <div class="info-item">
                    <span>Bienes asignados</span>
                    <strong>45</strong>
                </div>

                <div class="info-item">
                    <span>Personal</span>
                    <strong>8</strong>
                </div>
            </div>

            <button type="button" class="details-btn">Ver detalles</button>
        </article>

        <article class="card">
            <div class="card-top">
                <div class="area-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <div class="card-actions">
                    <button type="button" class="action-btn" aria-label="Editar"><i class="fa-solid fa-pen"></i></button>
                    <button type="button" class="action-btn action-danger" aria-label="Eliminar"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>

            <h3>Administraci&oacute;n</h3>
            <p class="responsable">Responsable: Mar&iacute;a Garc&iacute;a</p>

            <div class="linea"></div>

            <div class="datos">
                <div class="info-item">
                    <span>Bienes asignados</span>
                    <strong>32</strong>
                </div>

                <div class="info-item">
                    <span>Personal</span>
                    <strong>12</strong>
                </div>
            </div>

            <button type="button" class="details-btn">Ver detalles</button>
        </article>

        <article class="card">
            <div class="card-top">
                <div class="area-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <div class="card-actions">
                    <button type="button" class="action-btn" aria-label="Editar"><i class="fa-solid fa-pen"></i></button>
                    <button type="button" class="action-btn action-danger" aria-label="Eliminar"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>

            <h3>Recursos Humanos</h3>
            <p class="responsable">Responsable: Carlos L&oacute;pez</p>

            <div class="linea"></div>

            <div class="datos">
                <div class="info-item">
                    <span>Bienes asignados</span>
                    <strong>28</strong>
                </div>

                <div class="info-item">
                    <span>Personal</span>
                    <strong>6</strong>
                </div>
            </div>

            <button type="button" class="details-btn">Ver detalles</button>
        </article>
    </div>
@endsection
