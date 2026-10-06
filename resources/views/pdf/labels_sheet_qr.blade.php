<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hojas de Etiquetas Adhesivas (3x6) - JSBolsas Pro</title>
    <style>
        @page {
            margin: 8mm 6mm 8mm 6mm;
            size: letter portrait;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            background: #ffffff;
        }

        .labels-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3mm 2.5mm;
        }

        .label-cell {
            width: 33.33%;
            height: 38mm;
            border: 1px dashed #475569;
            padding: 2.5mm 3mm;
            vertical-align: top;
            background: #ffffff;
            border-radius: 4px;
        }

        .label-header {
            text-align: center;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
            border-bottom: 1px solid #000000;
            padding-bottom: 1.5px;
            margin-bottom: 3px;
            line-height: 1.1;
        }

        .label-presentation {
            font-size: 6pt;
            display: block;
            margin-top: 1px;
            color: #334155;
        }

        table.inner-table {
            width: 100%;
            border-collapse: collapse;
        }

        td.info-col {
            width: 60%;
            font-size: 6pt;
            line-height: 1.25;
            vertical-align: middle;
            word-break: break-word;
        }

        td.info-col p {
            margin: 1px 0;
        }

        td.qr-col {
            width: 40%;
            text-align: center;
            vertical-align: middle;
        }

        img.qr-img {
            width: 16mm;
            height: 16mm;
            display: block;
            margin: 0 auto;
        }

        .sku-tag {
            font-size: 5.5pt;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            margin-top: 1px;
            text-align: center;
        }

        .qr-code-text {
            font-size: 5pt;
            font-family: monospace;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @php
        $chunks = array_chunk($labels, 18); // 18 etiquetas por página carta (3x6)
    @endphp

    @foreach($chunks as $pageIndex => $pageLabels)
        <table class="labels-grid">
            @php
                $rows = array_chunk($pageLabels, 3); // 3 columnas por fila
            @endphp

            @foreach($rows as $rowLabels)
                <tr>
                    @foreach($rowLabels as $label)
                        <td class="label-cell">
                            <div class="label-header">
                                {{ $label['product_name'] }}
                                <span class="label-presentation">{{ $label['presentation_info'] }}</span>
                            </div>

                            <table class="inner-table">
                                <tr>
                                    <td class="info-col">
                                        <p>Operador: <strong>{{ $label['operator_name'] }}</strong></p>
                                        <p>Fecha: <strong>{{ $label['production_date'] }}</strong></p>
                                        <p>Aprobó: <strong>{{ $label['approver_name'] }}</strong></p>
                                        @if($label['is_variable'])
                                            <p>Peso Real: <strong>{{ number_format($label['weight_kg'], 2) }} Kg</strong></p>
                                        @endif
                                        <p>Lote: <strong>{{ $label['batch_code'] }}</strong></p>
                                    </td>
                                    <td class="qr-col">
                                        @if(class_exists('\Milon\Barcode\DNS2D'))
                                            <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($label['qr_code'], 'QRCODE', 4, 4) }}" class="qr-img" alt="QR">
                                        @endif
                                        <div class="sku-tag">{{ $label['sku'] }}</div>
                                        <div class="qr-code-text">{{ $label['qr_code'] }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    @endforeach

                    {{-- Completar celdas vacías si la última fila tiene menos de 3 columnas --}}
                    @for($c = count($rowLabels); $c < 3; $c++)
                        <td class="label-cell" style="border: none;"></td>
                    @endfor
                </tr>
            @endforeach
        </table>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
