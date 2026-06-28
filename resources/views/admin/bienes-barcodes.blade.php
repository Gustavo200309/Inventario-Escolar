<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>C&oacute;digos de Barras</title>
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
            min-height: 44mm;
            break-inside: avoid;
        }
        .label svg {
            max-width: 95%;
            height: 28px;
            display: block;
        }
        .label .codigo {
            font-size: 11px;
            font-weight: bold;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }
        .label .nombre {
            font-size: 8px;
            color: #666;
            margin-top: 1px;
            max-width: 90%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
            @for($i = 0; $i < 10 && $counter < $total; $i++)
                @php $bien = $bienes[$counter]; $counter++; @endphp
                <div class="label">
                    {!! $bien->barcode_svg !!}
                    <div class="codigo">{{ $bien->codigo_barras }}</div>
                    <div class="nombre">{{ $bien->nombre_bien }}</div>
                </div>
            @endfor
        </div>
    @endwhile

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>