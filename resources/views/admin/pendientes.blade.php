@extends('layouts.admin')

@section('title', 'Bienes Pendientes')

@section('content')
    <div class="header">
        <div>
            <h1>Bienes Pendientes</h1>
            <p>Gestiona los bienes que requieren atenci&oacute;n</p>
        </div>
    </div>

    <div class="stats">
        <article class="stat-card">
            <h3>Total pendientes</h3>
            <span class="green">5</span>
        </article>

        <article class="stat-card">
            <h3>Prioridad alta</h3>
            <span class="red">2</span>
        </article>

        <article class="stat-card">
            <h3>Sin asignar</h3>
            <span class="green">3</span>
        </article>
    </div>

    <div class="search-box">
        <div class="search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar bienes pendientes...">
        </div>

        <select>
            <option>Todas las prioridades</option>
            <option>Alta</option>
            <option>Media</option>
            <option>Baja</option>
        </select>
    </div>

    <article class="item">
        <div class="priority high">Alta</div>

        <div class="item-top">
            <div class="alert-icon">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>

            <div class="item-info">
                <h3>Laptop HP ProBook 450</h3>
                <div class="code">INV-2024-020</div>

                <div class="details">
                    <div>
                        <p>Raz&oacute;n</p>
                        <strong>Sin asignar</strong>
                    </div>

                    <div>
                        <p>Estado</p>
                        <strong>Pendiente de asignaci&oacute;n</strong>
                    </div>

                    <div>
                        <p>Fecha de registro</p>
                        <strong>14/5/2024</strong>
                    </div>
                </div>

                <div class="buttons">
                    <button type="button" class="btn btn-outline">
                        <i class="fa-solid fa-eye"></i>
                        Ver detalles
                    </button>

                    <button type="button" class="btn btn-green">
                        <i class="fa-solid fa-pen"></i>
                        Resolver
                    </button>
                </div>
            </div>
        </div>
    </article>

    <article class="item">
        <div class="priority medium">Media</div>

        <div class="item-top">
            <div class="alert-icon">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>

            <div class="item-info">
                <h3>Monitor Dell 24 pulgadas</h3>
                <div class="code">INV-2024-021</div>

                <div class="details">
                    <div>
                        <p>Raz&oacute;n</p>
                        <strong>Mantenimiento</strong>
                    </div>

                    <div>
                        <p>Estado</p>
                        <strong>Revisi&oacute;n t&eacute;cnica</strong>
                    </div>

                    <div>
                        <p>Fecha de registro</p>
                        <strong>16/5/2024</strong>
                    </div>
                </div>

                <div class="buttons">
                    <button type="button" class="btn btn-outline">
                        <i class="fa-solid fa-eye"></i>
                        Ver detalles
                    </button>

                    <button type="button" class="btn btn-green">
                        <i class="fa-solid fa-pen"></i>
                        Resolver
                    </button>
                </div>
            </div>
        </div>
    </article>
@endsection
