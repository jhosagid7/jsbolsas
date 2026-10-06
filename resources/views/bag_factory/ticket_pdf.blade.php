<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas Térmicas - JSBolsas Pro</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .ticket-page {
            page-break-after: always;
            padding: {{ ($size ?? '80mm') === '58mm' ? '1.5mm' : '2mm' }};
            width: 100%;
        }

        .ticket-container {
            border: 2px solid #000000;
            padding: {{ ($size ?? '80mm') === '58mm' ? '1.5mm 2mm' : '2.5mm 3.5mm' }};
            background: #ffffff;
            position: relative;
        }

        .ticket-header {
            text-align: center;
            font-weight: 900;
            font-size: {{ ($size ?? '80mm') === '58mm' ? '8pt' : '10.5pt' }};
            text-transform: uppercase;
            border-bottom: 2px solid #000000;
            padding-bottom: 2px;
            margin-bottom: 3px;
            line-height: 1.15;
            letter-spacing: 0.2px;
        }

        .ticket-presentation {
            font-size: {{ ($size ?? '80mm') === '58mm' ? '6.5pt' : '8pt' }};
            display: block;
            margin-top: 1px;
            font-weight: 800;
        }

        table.ticket-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td.ticket-info {
            vertical-align: middle;
            width: {{ ($size ?? '80mm') === '58mm' ? '58%' : '62%' }};
            font-size: {{ ($size ?? '80mm') === '58mm' ? '6.5pt' : '8.5pt' }};
            line-height: 1.25;
            word-break: break-word;
        }

        td.ticket-info p {
            margin: 2px 0;
            white-space: nowrap;
        }

        .info-label {
            font-weight: 900;
            color: #000000;
        }



        td.ticket-qr {
            vertical-align: middle;
            text-align: center;
            width: {{ ($size ?? '80mm') === '58mm' ? '42%' : '38%' }};
        }

        img.qr-img {
            display: block;
            margin: 0 auto;
            width: {{ ($size ?? '80mm') === '58mm' ? '15mm' : '22mm' }};
            height: {{ ($size ?? '80mm') === '58mm' ? '15mm' : '22mm' }};
        }

        .ticket-sku {
            font-size: {{ ($size ?? '80mm') === '58mm' ? '6pt' : '8pt' }};
            font-family: 'Courier New', Courier, monospace;
            font-weight: 900;
            margin-top: 2px;
            text-align: center;
            letter-spacing: 0.3px;
        }

        .ticket-qr-code {
            font-size: {{ ($size ?? '80mm') === '58mm' ? '5pt' : '7pt' }};
            font-family: monospace;
            font-weight: 900;
            text-align: center;
        }
    </style>
</head>
<body>
    @foreach($labels as $label)
        <div class="ticket-page">
            <div class="ticket-container">
                <div class="ticket-header">
                    {{ $label['product_name'] }}
                    <span class="ticket-presentation">{{ $label['presentation_info'] }}</span>
                </div>

                <table class="ticket-table">
                    <tr>
                        <td class="ticket-info">
                            <p><span class="info-label">OPERADOR:</span> <strong>{{ $label['operator_name'] }}</strong></p>
                            <p><span class="info-label">FECHA:</span> <strong>{{ $label['production_date'] }}</strong></p>
                            <p><span class="info-label">APROBÓ:</span> <strong>{{ $label['approver_name'] }}</strong></p>
                            @if($label['is_variable'])
                                <p><span class="info-label">PESO:</span> <strong>{{ number_format($label['weight_kg'], 2) }} Kg</strong></p>
                            @endif
                            <p><span class="info-label">LOTE:</span> <strong>{{ $label['batch_code'] }}</strong></p>
                        </td>
                        <td class="ticket-qr">
                            @if(class_exists('\Milon\Barcode\DNS2D'))
                                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG(!empty($label['sku']) && $label['sku'] !== 'S/SKU' ? $label['sku'] : $label['qr_code'], 'QRCODE', 4, 4) }}" class="qr-img" alt="QR">
                            @endif
                            <div class="ticket-sku">{{ $label['sku'] }}</div>
                            <div class="ticket-qr-code">{{ $label['batch_code'] }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach
</body>
</html>
