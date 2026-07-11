@extends('layouts.admin')

@section('title', 'Resultado del escaneo')

@section('content')
<div class="escanear-container">
    <div class="escanear-header">
        <a href="{{ route('admin.escanear') }}" class="btn-secundario" style="margin-bottom:8px;">
            <i class="fa-solid fa-arrow-left"></i> Escanear otro
        </a>
        <h1><i class="fa-solid fa-barcode"></i> Resultado</h1>
        <p class="escanear-sub">Código escaneado: <strong>{{ $codigo }}</strong></p>
    </div>

    @if($bien)
        <div class="resultado-card">
            <div class="resultado-estado">
                <span class="tag tag-{{ strtolower($bien->estatus) }}">{{ $bien->estatus }}</span>
            </div>

            <h2 class="resultado-nombre">{{ $bien->nombre_bien }}</h2>

            @if($bien->barcode_data_uri)
                <div class="resultado-barcode">
                    <img src="{{ $bien->barcode_data_uri }}" alt="Código de barras" height="50">
                    <span class="barcode-text">{{ $bien->codigo_barras }}</span>
                </div>
            @endif

            <div class="resultado-grid">
                <div class="resultado-item">
                    <span class="resultado-label">No. Inventario</span>
                    <span class="resultado-value">{{ $bien->no_inventario ?: '—' }}</span>
                </div>
                <div class="resultado-item">
                    <span class="resultado-label">ID SEP</span>
                    <span class="resultado-value">{{ $bien->id_sep ?: '—' }}</span>
                </div>
                <div class="resultado-item">
                    <span class="resultado-label">Marca</span>
                    <span class="resultado-value">{{ $bien->marca ?: '—' }}</span>
                </div>
                <div class="resultado-item">
                    <span class="resultado-label">Modelo</span>
                    <span class="resultado-value">{{ $bien->modelo ?: '—' }}</span>
                </div>
                <div class="resultado-item">
                    <span class="resultado-label">Serie</span>
                    <span class="resultado-value">{{ $bien->serie ?: '—' }}</span>
                </div>
                <div class="resultado-item">
                    <span class="resultado-label">Adquisición</span>
                    <span class="resultado-value">{{ $bien->adq ?: '—' }}</span>
                </div>

                <div class="resultado-item">
                    <span class="resultado-label">Fecha registro</span>
                    <span class="resultado-value">{{ $bien->fecha_registro ? $bien->fecha_registro->format('d/m/Y') : '—' }}</span>
                </div>
            </div>

            <div class="resultado-seccion">
                <h3><i class="fa-solid fa-building"></i> Ubicación</h3>
                <div class="resultado-grid">
                    <div class="resultado-item">
                        <span class="resultado-label">Área</span>
                        <span class="resultado-value">{{ $bien->area ? $bien->area->nombre_area : 'Sin área' }}</span>
                    </div>
                    <div class="resultado-item">
                        <span class="resultado-label">Responsable</span>
                        <span class="resultado-value">
                            @if($bien->personal)
                                {{ $bien->personal->nombre }} {{ $bien->personal->apellido_paterno }}
                            @else
                                Sin asignar
                            @endif
                        </span>
                    </div>
                    @if($bien->personal && $bien->personal->puesto)
                        <div class="resultado-item">
                            <span class="resultado-label">Puesto</span>
                            <span class="resultado-value">{{ $bien->personal->puesto }}</span>
                        </div>
                    @endif
                    @if($bien->personal && $bien->personal->correo)
                        <div class="resultado-item">
                            <span class="resultado-label">Correo</span>
                            <span class="resultado-value">{{ $bien->personal->correo }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="resultado-acciones">
                <a href="{{ route('admin.escanear') }}" class="btn-escanear" style="background:var(--primary);">
                    <i class="fa-solid fa-barcode"></i> Escanear otro
                </a>
                <a href="{{ route('admin.bienes') }}" class="btn-escanear" style="background:var(--hover);color:var(--text);border:1px solid var(--border);">
                    <i class="fa-solid fa-list"></i> Ver todos los bienes
                </a>
            </div>
        </div>
    @else
        <div class="resultado-not-found">
            <i class="fa-solid fa-circle-exclamation"></i>
            <h2>Bien no encontrado</h2>
            <p>No se encontró ningún bien con el código <strong>{{ $codigo }}</strong>.</p>
            <div class="resultado-acciones" style="margin-top:16px;">
                <a href="{{ route('admin.escanear') }}" class="btn-escanear" style="background:var(--primary);">
                    <i class="fa-solid fa-barcode"></i> Intentar de nuevo
                </a>
            </div>
        </div>
    @endif
</div>

<style>
.escanear-container {
    max-width: 480px;
    margin: 0 auto;
    padding: 20px 16px;
}
.escanear-header h1 {
    font-size: 22px;
    color: var(--heading);
    margin: 0 0 4px;
}
.escanear-sub {
    color: var(--muted);
    font-size: 14px;
    margin: 0 0 16px;
    word-break: break-all;
}
.escanear-sub strong { color: var(--text); }
.resultado-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 20px;
    box-shadow: var(--shadow);
}
.resultado-estado { margin-bottom: 12px; }
.tag {
    display: inline-block;
    padding: 5px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
}
.tag-disponible { background: var(--success-bg); color: var(--success-text); }
.tag-asignado { background: var(--info-bg); color: var(--info-text); }
.tag-pendiente { background: var(--warning-bg); color: var(--warning); }
.tag-baja { background: var(--danger-bg); color: var(--danger); }
.resultado-nombre {
    font-size: 20px;
    color: var(--heading);
    margin: 0 0 16px;
    font-weight: 700;
}
.resultado-barcode {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 16px;
    background: var(--surface-strong);
    border: 1px solid var(--border);
    border-radius: 12px;
    margin-bottom: 20px;
}
.barcode-text {
    font-family: monospace;
    font-size: 11px;
    color: var(--muted);
    letter-spacing: 0.5px;
    word-break: break-all;
    text-align: center;
}
.resultado-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}
.resultado-item {
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding: 12px;
    background: var(--surface-strong);
    border: 1px solid var(--border);
    border-radius: 10px;
}
.resultado-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--text-soft);
}
.resultado-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--heading);
    word-break: break-word;
}
.resultado-seccion {
    border-top: 1px solid var(--border);
    padding-top: 16px;
    margin-top: 4px;
    margin-bottom: 16px;
}
.resultado-seccion h3 {
    font-size: 15px;
    color: var(--heading);
    margin: 0 0 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.resultado-seccion h3 i { color: var(--primary); }
.resultado-acciones {
    display: flex;
    gap: 10px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}
.resultado-acciones .btn-escanear {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 14px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    text-decoration: none;
    color: #fff;
    transition: all 0.2s;
}
.resultado-acciones .btn-escanear:hover { opacity: 0.9; }
.resultado-not-found {
    text-align: center;
    padding: 40px 20px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
}
.resultado-not-found i {
    font-size: 48px;
    color: var(--danger);
    margin-bottom: 12px;
}
.resultado-not-found h2 {
    font-size: 20px;
    color: var(--heading);
    margin: 0 0 8px;
}
.resultado-not-found p {
    color: var(--muted);
    font-size: 14px;
    margin: 0;
    word-break: break-all;
}
.resultado-not-found p strong { color: var(--text); }
@media(max-width:480px) {
    .escanear-container { padding: 12px 10px; }
    .resultado-grid { grid-template-columns: 1fr; }
    .resultado-acciones { flex-direction: column; }
}
</style>
@endsection
