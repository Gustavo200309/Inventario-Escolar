<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>C&oacute;digos QR</title>
    <style>
        @page {
            margin: 12mm 10mm;
            size: letter;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
        }
        .page {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            page-break-after: always;
            min-height: 240mm;
            align-content: start;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .label {
            border: 1px dashed #ccc;
            border-radius: 4px;
            padding: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 60mm;
            break-inside: avoid;
        }
        .qr-box {
            width: 40mm;
            height: 40mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-box svg {
            width: 40mm;
            height: 40mm;
            display: block;
        }
        .label-idsep {
            margin-top: 0.8mm;
            font-size: 8px;
            font-weight: bold;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media print {
            .label {
                border-color: #ddd;
            }
        }
    </style>
</head>
<body>
    @php
        $counter = 0;
        $total = $bienes->count();
    @endphp

    @while($counter < $total)
        <div class="page">
            @for($i = 0; $i < 8 && $counter < $total; $i++)
                @php $bien = $bienes[$counter]; $counter++; @endphp
                <div class="label">
                    <div class="qr-box">{!! $bien->qr_svg !!}</div>
                    <div class="label-idsep">{{ $bien->id_sep ?? $bien->codigo_barras }}</div>
                </div>
            @endfor
        </div>
    @endwhile
</body>
</html>
