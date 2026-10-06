<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Oficial de Levantamiento de Producción - Plásticos M&F</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            size: letter portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #222;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 12px;
            font-weight: bold;
            color: #0284c7;
            margin: 2px 0 0 0;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 15px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #0f172a;
            text-align: left;
        }
        table.data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: top;
        }
        .day-header {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
            font-weight: bold;
            font-size: 10px !important;
            text-transform: uppercase;
        }
        .text-center { text-align: center !important; }
        .text-end { text-align: right !important; }
        .fw-bold { font-weight: bold !important; }
        .rolls-list {
            margin: 3px 0 0 0;
            padding-left: 12px;
            font-size: 8px;
            color: #475569;
        }
        .signatures {
            margin-top: 35px;
            width: 100%;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto;
            padding-top: 4px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }
        .no-print {
            display: block;
            margin-bottom: 15px;
            text-align: right;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 16px; background-color: #0284c7; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            🖨️ Imprimir / Guardar como PDF
        </button>
    </div>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td width="60%">
                <h1 class="title">PLÁSTICOS M&F, C.A.</h1>
                <div class="subtitle">FÁBRICA DE BOLSAS Y BOBINAS • CONTROL DE PRODUCCIÓN</div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">
                    RIF: J-40892019-0 • Sistema de Gestión y Levantamiento JSBolsas Pro
                </div>
            </td>
            <td width="40%" class="text-end">
                <div style="font-size: 14px; font-weight: bold; color: #0f172a;">REPORTE DE LEVANTAMIENTO</div>
                <div style="font-size: 9px; color: #64748b;">Emisión: {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}</div>
                <div style="font-size: 9px; color: #64748b;">Total Registros: {{ $allProductions->count() }}</div>
            </td>
        </tr>
    </table>

    <!-- Executive Financial Balance Box -->
    <div class="info-box" style="background-color: #f1f5f9; border-left: 4px solid #0284c7;">
        <table width="100%">
            <tr>
                <td width="25%">
                    <div style="font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: bold;">Ingresos Proyectados</div>
                    <div style="font-size: 13px; font-weight: bold; color: #0369a1; margin-top: 2px;">${{ number_format($financials['total_income'], 2) }}</div>
                </td>
                <td width="25%">
                    <div style="font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: bold;">Costo Total Producción</div>
                    <div style="font-size: 13px; font-weight: bold; color: #b91c1c; margin-top: 2px;">${{ number_format($financials['total_cost'], 2) }}</div>
                    <div style="font-size: 7.5px; color: #64748b; margin-top: 1px;">MP: ${{ number_format($financials['total_raw_cost'], 2) }} • Fijo: ${{ number_format($financials['total_fixed_cost'], 2) }}</div>
                </td>
                <td width="25%">
                    <div style="font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: bold;">Utilidad Neta Real</div>
                    <div style="font-size: 13px; font-weight: bold; color: {{ $financials['net_profit'] >= 0 ? '#15803d' : '#b91c1c' }}; margin-top: 2px;">
                        ${{ number_format($financials['net_profit'], 2) }}
                    </div>
                    <div style="font-size: 7.5px; color: #15803d; font-weight: bold; margin-top: 1px;">Margen Real: {{ $financials['margin_percent'] }}%</div>
                </td>
                <td width="25%" class="text-end">
                    <div style="font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: bold;">Volumen Físico</div>
                    <div style="font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 2px;">{{ number_format($totalKg, 2) }} Kg</div>
                    <div style="font-size: 7.5px; color: #64748b; margin-top: 1px;">{{ number_format($totalPkgs, 0) }} unids. • {{ $groupedByDay->count() }} Día(s)</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla de Levantamiento Agrupada por Día -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="30%">Producto (Descripción / Medida)</th>
                <th width="12%" class="text-center">Cant.</th>
                <th width="15%" class="text-center">Peso Real</th>
                <th width="23%">Operario Fabricante</th>
                <th width="20%">Estado / Código</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedByDay as $dateStr => $items)
                @php
                    $dayKg = $items->sum('weight');
                    $dayQty = $items->sum('quantity');
                    $dayTitle = $dateStr !== 'Sin Fecha'
                        ? \Carbon\Carbon::parse($dateStr)->locale('es')->isoFormat('dddd DD-MM-YYYY')
                        : 'Sin Fecha';
                @endphp
                <tr class="day-header">
                    <td colspan="5">
                        📅 {{ strtoupper($dayTitle) }} &nbsp;&nbsp;|&nbsp;&nbsp; 
                        Subtotal Día: {{ number_format($dayQty, 0) }} Unidades &nbsp;•&nbsp; {{ number_format($dayKg, 2) }} Kg
                    </td>
                </tr>
                @foreach($items as $p)
                    @php
                        $isRoll = ($p->product && $p->product->is_variable_quantity) || 
                                  str_contains(strtoupper($p->product->name ?? ''), 'BOBINA') || 
                                  !empty($p->metadata);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $p->product->name ?? 'Producto' }}</strong>
                            @if($p->product && $p->product->sku)
                                <br><span style="font-size: 8px; color: #64748b;">SKU: {{ $p->product->sku }}</span>
                            @endif
                            @if(!empty($p->metadata) && is_array($p->metadata))
                                @php
                                    $pPdfRolls = isset($p->metadata['rolls']) && is_array($p->metadata['rolls'])
                                        ? $p->metadata['rolls']
                                        : (isset($p->metadata['roll']) && is_array($p->metadata['roll'])
                                            ? [$p->metadata['roll']]
                                            : (is_array($p->metadata) ? $p->metadata : []));
                                @endphp
                                @if(count($pPdfRolls) > 0)
                                    <ul class="rolls-list">
                                        @foreach($pPdfRolls as $r)
                                            @if(is_array($r))
                                                <li>
                                                    Rollo #{{ $loop->iteration }}: <strong>{{ $r['weight'] ?? 0 }} Kg</strong>
                                                    @if(!empty($r['color'])) | Color: {{ $r['color'] }} @endif
                                                    @if(!empty($r['batch'])) | Lote: {{ $r['batch'] }} @endif
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                        </td>
                        <td class="text-center">
                            {{ number_format($p->quantity, 0) }} {{ $isRoll ? 'Rollos' : 'Bultos' }}
                        </td>
                        <td class="text-center fw-bold">
                            {{ number_format($p->weight, 2) }} Kg
                        </td>
                        <td>
                            <strong>{{ $p->user->name ?? 'Operario' }}</strong>
                            <br><span style="font-size: 8px; color: #64748b;">Turno: {{ strtoupper($p->shift->shift_type ?? 'Diurno') }}</span>
                        </td>
                        <td>
                            {{ strtoupper($p->status) }}
                            @if($p->qr_code)
                                <br><span style="font-size: 8px; font-family: monospace;">{{ $p->qr_code }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td class="text-end">GRAN TOTAL:</td>
                <td class="text-center">{{ number_format($totalPkgs, 0) }}</td>
                <td class="text-center">{{ number_format($totalKg, 2) }} Kg</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Firmas Oficiales -->
    <table class="signatures">
        <tr>
            <td width="33%" align="center">
                <div class="signature-line">
                    OPERARIO(S) DE PLANTA<br>
                    <span style="font-size: 8px; font-weight: normal; color: #64748b;">Fabricación y Pesaje</span>
                </div>
            </td>
            <td width="33%" align="center">
                <div class="signature-line">
                    JEFE DE OPERACIONES / BÁSCULA<br>
                    <span style="font-size: 8px; font-weight: normal; color: #64748b;">Auditoría y Control Calidad</span>
                </div>
            </td>
            <td width="33%" align="center">
                <div class="signature-line">
                    ADMINISTRACIÓN / SUPER ADMIN<br>
                    <span style="font-size: 8px; font-weight: normal; color: #64748b;">Aprobación y Recepción POS</span>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
