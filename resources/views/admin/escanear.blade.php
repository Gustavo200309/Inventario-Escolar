@extends('layouts.admin')

@section('title', 'Escanear código de barras')

@section('content')
<div class="escanear-container">
    <div class="escanear-header">
        <a href="{{ route('admin.bienes') }}" class="btn-secundario" style="margin-bottom:8px;">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
        <h1><i class="fa-solid fa-barcode"></i> Escanear código</h1>
        <p class="escanear-sub">Apunta la cámara al código de barras del bien</p>
    </div>

    <div id="scanner-area" class="scanner-area">
        <div id="scanner-viewport" class="scanner-viewport"></div>
        <div id="scanner-loading" class="scanner-loading">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <span>Cargando cámara...</span>
        </div>
        <div id="scanner-overlay" class="scanner-overlay hidden">
            <div class="scanner-line"></div>
        </div>
    </div>

    <div class="scanner-controls">
        <button id="btn-start" class="btn-escanear" onclick="startScanner()">
            <i class="fa-solid fa-camera"></i> Iniciar escáner
        </button>
        <button id="btn-stop" class="btn-escanear btn-stop hidden" onclick="stopScanner()">
            <i class="fa-solid fa-stop"></i> Detener
        </button>
        <button id="btn-switch" class="btn-escanear btn-switch hidden" onclick="switchCamera()">
            <i class="fa-solid fa-camera-rotate"></i> Cambiar cámara
        </button>
    </div>

    <div id="manual-entry" class="manual-entry">
        <p>O ingresa el código manualmente:</p>
        <form id="manual-form" onsubmit="goToResult(event)">
            <input type="text" id="manual-code" placeholder="Ej: INV-00001" autocomplete="off">
            <button type="submit" class="btn-escanear" style="background:var(--primary);">
                <i class="fa-solid fa-search"></i> Buscar
            </button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let html5QrCode = null;
    let cameras = [];
    let currentCameraIndex = 0;

    function startScanner() {
        const loading = document.getElementById('scanner-loading');
        const overlay = document.getElementById('scanner-overlay');
        const btnStart = document.getElementById('btn-start');
        const btnStop = document.getElementById('btn-stop');
        const btnSwitch = document.getElementById('btn-switch');

        loading.classList.remove('hidden');
        btnStart.classList.add('hidden');

        html5QrCode = new Html5Qrcode("scanner-viewport");

        Html5Qrcode.getCameras().then(function(devs) {
            if (!devs || devs.length === 0) {
                loading.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i><span>No se encontraron cámaras</span>';
                return;
            }
            cameras = devs;
            currentCameraIndex = 0;

            html5QrCode.start(
                cameras[0].id,
                {
                    fps: 10,
                    qrbox: { width: 280, height: 120 },
                    aspectRatio: 1.5,
                    disableFlip: false,
                    supportedScanTypes: [0]
                },
                function onScanSuccess(decodedText) {
                    stopScanner();
                    window.location.href = '{{ route("admin.escanear.buscar", "") }}/' + encodeURIComponent(decodedText);
                },
                function onScanFailure() {}
            ).then(function() {
                loading.classList.add('hidden');
                overlay.classList.remove('hidden');
                btnStop.classList.remove('hidden');
                if (cameras.length > 1) {
                    btnSwitch.classList.remove('hidden');
                }
            }).catch(function(err) {
                loading.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i><span>Error al iniciar cámara: ' + err + '</span>';
            });
        }).catch(function(err) {
            loading.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i><span>No se pudo acceder a la cámara</span>';
        });
    }

    function stopScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().then(function() {
                document.getElementById('scanner-overlay').classList.add('hidden');
                document.getElementById('btn-stop').classList.add('hidden');
                document.getElementById('btn-switch').classList.add('hidden');
                document.getElementById('btn-start').classList.remove('hidden');
            }).catch(function() {});
        }
    }

    function switchCamera() {
        if (cameras.length <= 1) return;
        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().then(function() {
                html5QrCode.start(
                    cameras[currentCameraIndex].id,
                    { fps: 10, qrbox: { width: 280, height: 120 }, aspectRatio: 1.5, disableFlip: false, supportedScanTypes: [0] },
                    function(decodedText) {
                        stopScanner();
                        window.location.href = '{{ route("admin.escanear.buscar", "") }}/' + encodeURIComponent(decodedText);
                    },
                    function() {}
                );
            });
        }
    }

    function goToResult(e) {
        e.preventDefault();
        var code = document.getElementById('manual-code').value.trim();
        if (code) {
            window.location.href = '{{ route("admin.escanear.buscar", "") }}/' + encodeURIComponent(code);
        }
    }
</script>

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
.escanear-header p {
    color: var(--muted);
    font-size: 14px;
    margin: 0 0 16px;
}
.scanner-area {
    position: relative;
    width: 100%;
    border-radius: 16px;
    overflow: hidden;
    background: #000;
    min-height: 260px;
    margin-bottom: 16px;
    border: 2px solid var(--border);
}
.scanner-viewport {
    width: 100%;
    min-height: 260px;
}
.scanner-viewport video {
    width: 100% !important;
    border-radius: 14px;
}
.scanner-loading {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--text-soft);
    font-size: 15px;
    background: var(--surface);
    border-radius: 14px;
}
.scanner-loading i { font-size: 28px; color: var(--primary); }
.scanner-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
}
.scanner-overlay.hidden { display: none; }
.scanner-line {
    width: 70%;
    height: 3px;
    background: var(--primary);
    border-radius: 2px;
    box-shadow: 0 0 12px var(--primary);
    animation: scanLine 2s ease-in-out infinite;
}
@keyframes scanLine {
    0%, 100% { transform: translateY(-40px); opacity: 0.7; }
    50% { transform: translateY(40px); opacity: 1; }
}
.scanner-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.btn-escanear {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 16px;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    background: var(--primary);
    color: #fff;
    transition: all 0.2s;
}
.btn-escanear:hover { background: var(--primary-dark); }
.btn-stop { background: var(--danger); color: #fff; }
.btn-stop:hover { background: #a12d2d; }
.btn-switch { background: var(--hover); color: var(--text); border: 1px solid var(--border); }
.btn-switch:hover { background: var(--secondary-hover); }
.hidden { display: none !important; }
.manual-entry {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 20px;
}
.manual-entry p {
    color: var(--muted);
    font-size: 14px;
    margin: 0 0 12px;
    text-align: center;
}
#manual-form {
    display: flex;
    gap: 8px;
}
#manual-form input {
    flex: 1;
    padding: 12px 14px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 15px;
    font-family: inherit;
    background: var(--surface-strong);
    color: var(--text);
    outline: none;
}
#manual-form input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--focus-ring); }
#manual-form .btn-escanear { flex: none; padding: 12px 20px; font-size: 14px; }
@media(max-width:480px) {
    .escanear-container { padding: 12px 10px; }
    .scanner-viewport { min-height: 220px; }
    .scanner-area { min-height: 220px; }
}
</style>
@endsection
