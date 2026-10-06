<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión de Etiquetas de Producción - JSBolsas Pro</title>
    <style id="dynamic-page-style">
        @media print {
            @page {
                margin: 0;
                size: portrait;
            }
        }
    </style>
    <style>
        /* ===================================================
           ESTILOS GENERALES Y SIMULADOR EN PANTALLA
           =================================================== */
        * {
            box-sizing: border-box;
        }

        body {
            background-color: #0f172a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #000000;
            -webkit-font-smoothing: antialiased;
        }

        .preview-controls {
            background: #ffffff;
            max-width: 680px;
            margin: 0 auto 16px auto;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.35);
            text-align: center;
        }

        .preview-controls h3 {
            margin: 0 0 4px 0;
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
        }

        .preview-controls p {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #64748b;
        }

        .btn-size {
            padding: 6px 12px;
            margin: 0 3px;
            border: 2px solid #0284c7;
            background: #f0f9ff;
            color: #0284c7;
            border-radius: 6px;
            font-weight: bold;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-size.active, .btn-size:hover {
            background: #0284c7;
            color: #ffffff;
        }

        .btn-actions {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-print {
            padding: 8px 16px;
            background: #10b981;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-weight: 900;
            font-size: 12px;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(16, 185, 129, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-print:hover {
            background: #059669;
        }

        .btn-pdf {
            padding: 8px 16px;
            background: #0284c7;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-weight: 900;
            font-size: 12px;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(2, 132, 199, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-pdf:hover {
            background: #0369a1;
        }

        .btn-back {
            padding: 8px 14px;
            background: #475569;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-back:hover {
            background: #334155;
        }

        /* ===================================================
           ESTRUCTURA COMPACTA DE CADA ETIQUETA FÍSICA
           =================================================== */
        .ticket-wrapper {
            margin: 0 auto;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .ticket-container {
            background: #ffffff;
            border: 2px solid #000000;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            margin: 0 auto;
            text-align: left;
            color: #000000;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            opacity: 0.05;
            z-index: 1;
            pointer-events: none;
        }

        .ticket-header {
            text-align: center;
            border-bottom: 2px solid #000000;
            margin-bottom: 4px;
            padding-bottom: 2px;
            position: relative;
            z-index: 2;
            line-height: 1.15;
        }

        .product-title {
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            word-break: break-word;
        }

        .ticket-presentation {
            font-weight: 800;
            letter-spacing: 0.3px;
            display: block;
            margin-top: 2px;
            text-transform: uppercase;
        }

        table.ticket-table {
            width: 100%;
            border-collapse: collapse;
            position: relative;
            z-index: 2;
            table-layout: fixed;
        }

        .ticket-info-td {
            vertical-align: middle;
            text-align: left;
            word-break: break-word;
        }

        .info-row {
            margin: 2px 0;
            line-height: 1.35;
            word-break: break-word;
        }

        .info-label {
            font-weight: 900;
            color: #000000;
            letter-spacing: 0.2px;
        }

        .info-val {
            font-weight: 700;
            color: #000000;
        }

        .info-weight-val {
            font-weight: 900;
            font-size: 1.08em;
        }

        .ticket-qr-td {
            vertical-align: middle;
            text-align: center;
        }

        .qr-img {
            display: block;
            margin: 0 auto;
            background: #ffffff;
            image-rendering: pixelated;
            image-rendering: -moz-crisp-edges;
            image-rendering: crisp-edges;
        }

        .ticket-sku {
            font-weight: 900;
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 0.5px;
            margin-top: 2px;
            text-transform: uppercase;
            text-align: center;
            word-break: break-all;
            line-height: 1.1;
        }

        .ticket-qr-code {
            font-weight: 900;
            font-family: monospace;
            color: #000000;
            margin-top: 1px;
            text-align: center;
            word-break: break-all;
            line-height: 1;
        }

        /* ===================================================
           TAMAÑO FORMATO 80mm
           =================================================== */
        .size-80mm {
            width: 76mm;
            padding: 2.5mm 3.5mm;
        }

        .size-80mm .product-title {
            font-size: 13pt;
        }

        .size-80mm .ticket-presentation {
            font-size: 9.5pt;
        }

        .size-80mm .ticket-info-td {
            width: 66%;
        }

        .size-80mm .info-row {
            font-size: 9.5pt;
        }

        .size-80mm .ticket-qr-td {
            width: 34%;
        }

        .size-80mm .qr-img {
            width: 21mm !important;
            height: 21mm !important;
        }

        .size-80mm .ticket-sku {
            font-size: 8pt;
        }

        .size-80mm .ticket-qr-code {
            font-size: 7pt;
        }

        /* ===================================================
           TAMAÑO FORMATO 58mm
           =================================================== */
        .size-58mm {
            width: 54mm;
            padding: 1.5mm 2.5mm;
            border-width: 1.5px;
        }

        .size-58mm .product-title {
            font-size: 9.5pt;
        }

        .size-58mm .ticket-presentation {
            font-size: 7.5pt;
        }

        .size-58mm .ticket-info-td {
            width: 58%;
        }

        .size-58mm .info-row {
            font-size: 7.5pt;
            margin: 1px 0;
            line-height: 1.25;
        }

        .size-58mm .ticket-qr-td {
            width: 42%;
        }

        .size-58mm .qr-img {
            width: 14mm !important;
            height: 14mm !important;
        }

        .size-58mm .ticket-sku {
            font-size: 6pt;
            letter-spacing: 0.3px;
            margin-top: 1px;
        }

        .size-58mm .ticket-qr-code {
            font-size: 5pt;
            letter-spacing: 0px;
        }

        /* ===================================================
           REGLAS EXACTAS DE IMPRESIÓN DIRECTA (@media print)
           =================================================== */
        @media print {
            @page {
                margin: 0mm !important;
                size: portrait;
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
                height: auto !important;
            }

            .no-print {
                display: none !important;
            }

            .watermark {
                display: none !important;
            }

            .ticket-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
                gap: 0 !important;
                width: 100% !important;
            }

            .ticket-container {
                box-shadow: none !important;
                border: 2px solid #000000 !important;
                margin: 0 auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: always !important;
                break-after: page !important;
                display: block !important;
            }

            .ticket-container:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
                margin-bottom: 0 !important;
            }

            .size-80mm {
                width: 76mm !important;
                max-width: 76mm !important;
                padding: 1mm 2.5mm !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            .size-58mm {
                width: 54mm !important;
                max-width: 54mm !important;
                padding: 0.8mm 2mm !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }
        }
    </style>
</head>
<body>

    <!-- Panel de Previsualización y Selector de Formato de Papel -->
    <div class="preview-controls no-print">
        <h3>🏷️ Módulo de Impresión de Etiquetas Térmicas (v2)</h3>
        <p>Total de etiquetas físicas a imprimir: <strong>{{ count($labels) }}</strong> 
            @if(isset($scope))
                <span class="badge" style="background:#0284c7; color:#fff; font-size:11px; margin-left:5px; padding:2px 8px; border-radius:4px;">Modo: {{ strtoupper($scope) }}</span>
            @endif
        </p>
        
        <div style="margin-bottom: 8px;">
            <span>Modo de Etiquetas: </span>
            <a href="?scope=kit" class="btn-size {{ ($scope ?? '') === 'kit' ? 'active' : '' }}" style="text-decoration:none;">📦 Kit Completo (Bulto + Desglose)</a>
            <a href="?scope=bulto" class="btn-size {{ ($scope ?? '') === 'bulto' ? 'active' : '' }}" style="text-decoration:none;">🏷️ Solo Bulto Master</a>
            <a href="?scope=millar" class="btn-size {{ ($scope ?? '') === 'millar' ? 'active' : '' }}" style="text-decoration:none;">🔘 Solo Unidades / Bobinas</a>
        </div>

        <div>
            <span>Formato de Papel: </span>
            <button type="button" class="btn-size active" id="btn-80mm" onclick="changePaperSize('80mm')">Rollo 80mm (Grande / Zebra)</button>
            <button type="button" class="btn-size" id="btn-58mm" onclick="changePaperSize('58mm')">Rollo 58mm (Portátil / Mini)</button>
        </div>

        <div class="btn-actions">
            <button type="button" class="btn-print" onclick="triggerPrint()">
                🖨️ MANDAR A IMPRIMIR
            </button>
            <button type="button" class="btn-pdf" onclick="downloadPdf()">
                📄 DESCARGAR PDF
            </button>
            <a href="{{ route('labels.index') }}" class="btn-back">
                🏷️ Generador
            </a>
            <a href="{{ route('bag-factory.label-audits') }}" class="btn-back" style="background:#b91c1c;">
                🛡️ Auditoría
            </a>
        </div>
    </div>

    <!-- Contenedor de Etiquetas Individuales -->
    <div class="ticket-wrapper">
        @forelse($labels as $label)
            <div class="ticket-container size-80mm" id="ticket-{{ $loop->index }}">
                
                <!-- Watermark Logo (Solo en Pantalla) -->
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
                    <div class="product-title">{{ $label['product_name'] }}</div>
                    <span class="ticket-presentation">{{ $label['presentation_info'] }}</span>
                </div>

                <!-- Cuerpo de Datos y Código QR con Tabla Fija -->
                <table class="ticket-table">
                    <tr>
                        <!-- Columna Izquierda: Datos Operativos de Fábrica -->
                        <td class="ticket-info-td">
                            <div class="info-row"><span class="info-label">OPERADOR:</span> <span class="info-val">{{ $label['operator_name'] }}</span></div>
                            <div class="info-row"><span class="info-label">FECHA:</span> <span class="info-val">{{ $label['production_date'] }}</span></div>
                            <div class="info-row"><span class="info-label">APROBÓ:</span> <span class="info-val">{{ $label['approver_name'] }}</span></div>

                            {{-- OCULTACIÓN SELECTIVA: Sólo se muestra Peso Real si es Bobina de Peso Variable --}}
                            @if($label['is_variable'])
                                <div class="info-row"><span class="info-label">Peso Real:</span> <span class="info-val info-weight-val">{{ number_format($label['weight_kg'], 2) }} Kg</span></div>
                            @endif

                            <div class="info-row"><span class="info-label">LOTE:</span> <span class="info-val font-monospace">{{ $label['batch_code'] }}</span></div>
                        </td>

                        <!-- Columna Derecha: Código QR en Imagen PNG Base64 Ultraligera -->
                        <td class="ticket-qr-td">
                            @php
                                $qrVal = (!empty($label['sku']) && $label['sku'] !== 'S/SKU') ? $label['sku'] : $label['qr_code'];
                            @endphp
                            @if(class_exists('\Milon\Barcode\DNS2D'))
                                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($qrVal, 'QRCODE', 4, 4) }}" class="qr-img" alt="QR Code">
                            @else
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qrVal) }}" class="qr-img" alt="QR Code">
                            @endif
                            <div class="ticket-sku">{{ $label['sku'] }}</div>
                            <div class="ticket-qr-code">{{ $label['batch_code'] }}</div>
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
        let currentSize = '80mm';
        let printAuditLogged = false;

        function sendPrintAudit() {
            if (printAuditLogged) return;
            printAuditLogged = true;

            const payload = {
                _token: '{{ csrf_token() }}',
                labels: @json($labels)
            };

            const confirmUrl = "{{ route('labels.confirm_print') }}";

            if (navigator.sendBeacon) {
                const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
                navigator.sendBeacon(confirmUrl, blob);
            } else {
                fetch(confirmUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                }).catch(err => console.error('Error logging print audit:', err));
            }
        }

        function triggerPrint() {
            sendPrintAudit();
            window.print();
        }

        window.addEventListener('afterprint', function() {
            sendPrintAudit();
        });

        function changePaperSize(size) {
            currentSize = size;
            const tickets = document.querySelectorAll('.ticket-container');
            const btn80 = document.getElementById('btn-80mm');
            const btn58 = document.getElementById('btn-58mm');
            const dynamicPageStyle = document.getElementById('dynamic-page-style');

            if (size === '80mm') {
                tickets.forEach(t => {
                    t.classList.remove('size-58mm');
                    t.classList.add('size-80mm');
                });
                btn80.classList.add('active');
                btn58.classList.remove('active');
                if (dynamicPageStyle) {
                    dynamicPageStyle.innerHTML = '@media print { @page { margin: 0; size: portrait; } }';
                }
            } else {
                tickets.forEach(t => {
                    t.classList.remove('size-80mm');
                    t.classList.add('size-58mm');
                });
                btn58.classList.add('active');
                btn80.classList.remove('active');
                if (dynamicPageStyle) {
                    dynamicPageStyle.innerHTML = '@media print { @page { margin: 0; size: portrait; } }';
                }
            }
        }

        function downloadPdf() {
            @if(isset($pdfUrl) && !empty($pdfUrl))
                const basePdfUrl = "{{ $pdfUrl }}";
                const separator = basePdfUrl.includes('?') ? '&' : '?';
                window.open(basePdfUrl + separator + 'size=' + currentSize, '_blank');
            @else
                triggerPrint();
            @endif
        }
    </script>
</body>
</html>