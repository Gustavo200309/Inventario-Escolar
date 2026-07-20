<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del bien</title>
    <style>{!! file_get_contents(public_path('css/admin.css')) !!}</style>
    <style>
        body.public-detail-page {
            min-height: 100vh;
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .public-detail-shell {
            width: min(100%, 660px);
            margin: 0 auto;
            padding: 24px 16px;
        }
        .public-detail-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 26px 28px 30px;
        }
        .public-detail-header {
            border-bottom: 1px solid var(--border);
            margin-bottom: 22px;
            padding-bottom: 20px;
        }
        .public-detail-header h1 {
            color: var(--primary-dark);
            font-size: 24px;
            line-height: 1.2;
            margin: 0;
        }
.public-barcode-detail .public-barcode-img {
            display: block;
            width: 220px;
            height: 220px;
            object-fit: contain;
            margin: 6px auto 8px;
        }
.public-barcode-code {
            display: block;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            word-break: break-all;
        }
        .public-detail-empty p {
            color: var(--muted);
            margin: 0;
        }
        @media (max-width: 560px) {
            .public-detail-shell { padding: 12px 10px; }
            .public-detail-card { padding: 20px 16px 22px; }
            .public-detail-header h1 { font-size: 22px; }
        }
    </style>
</head>
<body class="public-detail-page">
    <main class="public-detail-shell">
        @if($bien)
            <section class="public-detail-card">
                <header class="public-detail-header">
                    <h1>Detalles del bien</h1>
                </header>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">No. Inventario</span>
                        <span class="detail-value">{{ $bien->no_inventario ?: 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">ID SEP</span>
                        <span class="detail-value">{{ $bien->id_sep ?: 'N/A' }}</span>
                    </div>
                    <div class="detail-item" style="grid-column:1/-1;">
                        <span class="detail-label">Nombre del bien</span>
                        <span class="detail-value">{{ $bien->nombre_bien ?: 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Marca</span>
                        <span class="detail-value">{{ $bien->marcaRelacion?->nombre_marca ?? $bien->marca ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Modelo</span>
                        <span class="detail-value">{{ $bien->modelo ?: 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Serie</span>
                        <span class="detail-value">{{ $bien->serie ?: 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Area</span>
                        <span class="detail-value">{{ $bien->area?->nombre_area ?? 'Sin area' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Responsable</span>
                        <span class="detail-value">{{ $bien->personal?->nombre ?? 'Sin asignar' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value">{{ $bien->estatus ?: 'N/A' }}</span>
                    </div>
                    <div class="detail-item public-barcode-detail" style="grid-column:1/-1;text-align:center;">
                        <span class="detail-label">C&oacute;digo QR</span>
                        @if($bien->qr_data_uri)
                            <img src="{{ $bien->qr_data_uri }}" alt="{{ $bien->codigo_barras }}" class="barcode-img public-barcode-img">
                            <span class="public-barcode-code">{{ $bien->codigo_barras }}</span>
                        @else
                            <span class="detail-value">N/A</span>
                        @endif
                    </div>
                </div>
            </section>
        @else
            <section class="public-detail-card public-detail-empty">
                <header class="public-detail-header">
                    <h1>Bien no encontrado</h1>
                </header>
                <p>No se encontro ningun bien con el codigo {{ $codigo }}.</p>
            </section>
        @endif
    </main>
</body>
</html>