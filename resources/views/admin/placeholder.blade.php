@extends('layouts.admin')

@section('title', $pageTitle ?? 'Vista administrativa')

@section('content')
    <section class="page-header">
        <div>
            <h2>{{ $pageTitle ?? 'Vista' }}</h2>
            <p class="section-subtitle">{{ $pageSubtitle ?? 'Pantalla visual preparada para futuras vistas del administrador.' }}</p>
        </div>

        <div class="page-actions">
            <button type="button" class="btn-secondary">
                <i class="fa-solid fa-layer-group"></i>
                Vista preliminar
            </button>

            <button type="button" class="btn-primary">
                <i class="fa-solid fa-plus"></i>
                Acci&oacute;n visual
            </button>
        </div>
    </section>

    <section class="panel">
        <div class="panel-body">
            <div class="placeholder-box">
                <div class="brand-badge">
                    <i class="fa-solid fa-palette"></i>
                    Prototipo visual
                </div>

                <h3 class="section-title">{{ $pageTitle ?? 'M&oacute;dulo' }}</h3>
                <p class="helper-text">Esta vista no est&aacute; conectada a base de datos. Solo conserva la estructura visual y el estilo del panel administrador.</p>

                <div class="stat-grid">
                    <article class="stat-card">
                        <div class="label">Estado</div>
                        <div class="value">Mockup</div>
                    </article>

                    <article class="stat-card">
                        <div class="label">Modo</div>
                        <div class="value">Visual</div>
                    </article>

                    <article class="stat-card">
                        <div class="label">Datos</div>
                        <div class="value">Pendiente</div>
                    </article>
                </div>
            </div>
        </div>
    </section>
@endsection
