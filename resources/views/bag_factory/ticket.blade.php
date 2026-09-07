<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión de Etiquetas de Producción - JSBolsas Pro</title>
    <style>
        /* ===================================================
           ESTILOS GENERALES Y SIMULADOR EN PANTALLA
           =================================================== */
        * {
            box-sizing: border-box;
        }

        body {
            background-color: #1e293b;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 20px;
            color: #000;
        }

        .preview-controls {
            background: #ffffff;
            max-width: 600px;
            margin: 0 auto 25px auto;
            padding: 18px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            text-align: center;
        }

        .preview-controls h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            color: #0f172a;
        }

        .preview-controls p {
            margin: 0 0 14px 0;
            font-size: 13px;
            color: #64748b;
        }

        .btn-size {
            padding: 7px 14px;
            margin: 0 4px;
            border: 2px solid #0284c7;
            background: #f0f9ff;
            color: #0284c7;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-size.active, .btn-size:hover {
            background: #0284c7;
            color: #ffffff;
        }

        .btn-print {
            display: inline-block;
            margin-top: 14px;
            padding: 10px 24px;
            background: #10b981;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 900;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
        }

        .btn-print:hover {
            background: #059669;
        }

        /* ===================================================
           ESTRUCTURA DE CADA ETIQUETA FÍSICA
           =================================================== */
        .ticket-wrapper {
            margin: 0 auto;
            text-align: center;
        }

        .ticket-container {
            background: #ffffff;
            border: 2px solid #000000;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            margin: 0 auto 20px auto;
            text-align: left;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 140px;
            height: 140px;
            opacity: 0.06;
            z-index: 1;
            pointer-events: none;
        }

        .ticket-header {
            text-align: center;
            font-weight: 900;
            text-transform: uppercase;
            border-bottom: 2px solid #000000;
            margin-bottom: 6px;
            position: relative;
            z-index: 2;
            word-break: break-word;
            line-height: 1.15;
        }

        .ticket-presentation {
            font-weight: bold;
            font-size: 0.85em;
            letter-spacing: 0.5px;
            display: block;
            margin-top: 2px;
        }

        /* Estructura en tabla para máxima compatibilidad con Chromium y Drivers térmicos */
        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            position: relative;
            z-index: 2;
        }

        .ticket-info-td {
            vertical-align: middle;
            text-align: left;
        }

        .ticket-info-td p {
            margin: 3px 0;
            font-weight: normal;
            color: #000000;
            line-height: 1.25;
            white-space: nowrap;
        }

        .ticket-info-td p strong {
            font-weight: 900;
        }

        .ticket-qr-td {
            vertical-align: middle;
            text-align: center;
            width: 38%;
        }

        .qr-img {
            display: block;
            margin: 0 auto;
            background: #ffffff;
        }

        .ticket-sku {
            font-weight: 900;
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 1px;
            margin-top: 2px;
            text-transform: uppercase;
            text-align: center;
        }

        .ticket-qr-code {
            font-size: 8px;
            font-weight: bold;
            font-family: monospace;
            color: #000000;
            margin-top: 1px;
            text-align: center;
        }

        /* ===================================================
           TAMAÑO FORMATO 80mm (Estándar 3x6 / Zebra)
           =================================================== */
        .size-80mm {
            width: 76mm;
            min-height: 44mm;
            padding: 4mm 5mm;
        }

        .size-80mm .ticket-header {
            font-size: 11pt;
            padding-bottom: 3px;
        }

        .size-80mm .ticket-info-td p {
            font-size: 8pt;
        }

        .size-80mm .qr-img {
            width: 22mm !important;
            height: 22mm !important;
        }

        .size-80mm .ticket-sku {
            font-size: 8pt;
        }

        /* ===================================================
           TAMAÑO FORMATO 58mm (Portátil de Cinturón / Xprinter)
           =================================================== */
        .size-58mm {
            width: 54mm;
            min-height: 34mm;
            padding: 2.5mm 3mm;
            border-width: 1.5px;
        }

        .size-58mm .ticket-header {
            font-size: 8pt;
            border-bottom-width: 1.5px;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }

        .size-58mm .ticket-presentation {
            font-size: 7pt;
        }

        .size-58mm .ticket-info-td p {
            font-size: 6.5pt;
            margin: 1.5px 0;
        }

        .size-58mm .qr-img {
            width: 17mm !important;
            height: 17mm !important;
        }

        .size-58mm .ticket-sku {
            font-size: 6.5pt;
        }

        /* ===================================================
           REGLAS EXACTAS DE IMPRESIÓN DIRECTA (@media print)
           ANTI-BLOQUEO CHROMIUM (100% CERO RECURSIÓN)
           =================================================== */
        @media print {
            @page {
                margin: 0;
                size: auto;
            }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background-color: #ffffff !important;
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                width: 100% !important;
            }

            .no-print {
                display: none !important;
            }

            .ticket-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
            }

            .ticket-container {
                box-shadow: none !important;
                border: 2px solid #000000 !important;
                margin: 0 auto !important;
                page-break-after: always !important;
                break-after: page !important;
                display: block !important;
            }

            .size-80mm {
                width: 76mm !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            .size-58mm {
                width: 54mm !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }
        }
    </style>
</head>
<body>

    <!-- Panel de Previsualización y Selector de Formato de Papel -->
    <div class="preview-controls no-print">
        <h3>🖨️ Módulo de Impresión de Etiquetas Térmicas (v2)</h3>
        <p>Total de etiquetas a imprimir: <strong>{{ count($labels) }}</strong></p>
        <div>
            <span>Formato de Papel: </span>
            <button type="button" class="btn-size active" id="btn-80mm" onclick="changePaperSize('80mm')">Rollo 80mm (Grande / Zebra)</button>
            <button type="button" class="btn-size" id="btn-58mm" onclick="changePaperSize('58mm')">Rollo 58mm (Portátil / Mini)</button>
        </div>
        <div>
            <button type="button" class="btn-print" onclick="window.print()">🖨️ MANDAR A IMPRIMIR ETIQUETAS</button>
        </div>
    </div>

    <!-- Contenedor de Etiquetas Individuales -->
    <div class="ticket-wrapper">
        @forelse($labels as $label)
            <div class="ticket-container size-80mm" id="ticket-{{ $loop->index }}">
                
                <!-- Watermark Logo -->
                @if(file_exists(public_path('images/logo_watermark.png')))
                    <img class="watermark" src="{{ asset('images/logo_watermark.png') }}" alt="Watermark">
                @else
                    <svg class="watermark" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" stroke="#000" stroke-width="4" fill="none"/>
                        <text x="50%" y="45%" text-anchor="middle" font-size="12" font-weight="bold" fill="#000">PLÁSTICOS</text>
                        <text x="50%" y="60%" text-anchor="middle" font-size="14" font-weight="900" fill="#000">M&F</text>
                    </svg>
                @endif

                <!-- Encabezado de la Etiqueta -->
                <div class="ticket-header">
                    {{ $label['product_name'] }}
                    <span class="ticket-presentation">{{ $label['presentation_info'] }}</span>
                </div>

                <!-- Cuerpo de Datos y Código QR con Tabla Nativa para evitar bloqueos de Layout -->
                <table class="ticket-table">
                    <tr>
                        <!-- Columna Izquierda: Datos Operativos de Fábrica -->
                        <td class="ticket-info-td">
                            <p>Operador: <strong>{{ $label['operator_name'] }}</strong></p>
                            <p>Fecha: <strong>{{ $label['production_date'] }}</strong></p>
                            <p>Aprobó: <strong>{{ $label['approver_name'] }}</strong></p>
                            
                            {{-- OCULTACIÓN SELECTIVA: Sólo se muestra Peso Real si es Bobina de Peso Variable --}}
                            @if($label['is_variable'])
                                <p>Peso Real: <strong>{{ number_format($label['weight_kg'], 2) }} Kg</strong></p>
                            @endif

                            <p>Lote: <strong>{{ $label['batch_code'] }}</strong></p>
                        </td>

                        <!-- Columna Derecha: Código QR en Imagen PNG Base64 Ultraligera -->
                        <td class="ticket-qr-td">
                            @if(class_exists('\Milon\Barcode\DNS2D'))
                                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($label['qr_code'], 'QRCODE', 4, 4) }}" class="qr-img" alt="QR Code">
                            @else
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($label['qr_code']) }}" class="qr-img" alt="QR Code">
                            @endif
                            <div class="ticket-sku">{{ $label['sku'] }}</div>
                            <div class="ticket-qr-code">{{ $label['qr_code'] }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        @empty
            <div class="preview-controls no-print" style="margin-top: 40px;">
                <p>No se encontraron etiquetas para imprimir con los parámetros seleccionados.</p>
            </div>
        @endforelse
    </div>

    <script>
        function changePaperSize(size) {
            const tickets = document.querySelectorAll('.ticket-container');
            const btn80 = document.getElementById('btn-80mm');
            const btn58 = document.getElementById('btn-58mm');

            if (size === '80mm') {
                tickets.forEach(t => {
                    t.classList.remove('size-58mm');
                    t.classList.add('size-80mm');
                });
                btn80.classList.add('active');
                btn58.classList.remove('active');
            } else {
                tickets.forEach(t => {
                    t.classList.remove('size-80mm');
                    t.classList.add('size-58mm');
                });
                btn58.classList.add('active');
                btn80.classList.remove('active');
            }
        }
    </script>
</body>
</html>