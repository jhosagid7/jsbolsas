<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Producción de Bolsas</title>
    <style type="text/css" media="screen">
        html {
            font-family: sans-serif;
            line-height: 1.15;
            margin: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            text-align: left;
            background-color: #fff;
            font-size: 10px;
            margin: 36pt;
        }

        h4 {
            margin-top: 0;
            margin-bottom: 0.5rem;
        }

        p {
            margin-top: 0;
            margin-bottom: 1rem;
        }

        strong {
            font-weight: bold;
        }

        img {
            vertical-align: middle;
            border-style: none;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            text-align: inherit;
        }

        h4, .h4 {
            margin-bottom: 0.5rem;
            font-weight: 500;
            line-height: 1.2;
            font-size: 1.5rem;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }

        .table th,
        .table td {
            padding: 0.5rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
            text-align: center;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-uppercase {
            text-transform: uppercase !important;
        }

        .bg-light {
            background-color: #f8f9fa;
        }

        .invoice-title {
            color: #2c3e50;
            font-weight: bold;
            font-size: 20px;
            margin: 0;
        }
        .report-title {
            color: #2c3e50;
            font-size: 16px;
            font-weight: bold;
             margin: 0;
        }
        .box-details {
            border: 1px solid #6B7280;
            border-radius: 15px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .watermark {
            position: fixed;
            top: 45%;
            left: 5%;
            width: 90%;
            text-align: center;
            opacity: 0.08;
            font-size: 70px;
            font-weight: bold;
            transform: rotate(-30deg);
            transform-origin: 50% 50%;
            z-index: -1000;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    @php
        $config = \App\Models\Configuration::first();
        $statusText = $statusOverride ?? ($production->status == 'sent' ? 'PROCESADO' : ($production->status == 'approved' ? 'APROBADO' : 'PENDIENTE'));
        
        if ($statusText == 'ORIGINAL' || $statusText == 'ORIGINAL LEVANTADO') {
            $watermarkColor = '#dc3545'; // Red
        } elseif ($statusText == 'APROBADO' || $statusText == 'APROBADA') {
            $watermarkColor = '#007bff'; // Blue
        } elseif ($statusText == 'PROCESADO') {
            $watermarkColor = '#28a745'; // Green
        } else {
            $watermarkColor = '#dc3545'; // Fallback Red
        }
    @endphp

    <div class="watermark" style="color: {{ $watermarkColor }};">
        {{ $statusText }}
    </div>

    {{-- Header --}}
    <table class="table mt-1" style="margin-bottom: 0;">
        <tbody>
            <tr>
                <td class="pl-0 border-0" width="25%" style="vertical-align: middle;">
                   @if($config && $config->logo && file_exists(public_path('storage/' . $config->logo)))
                        <img src="{{ public_path('storage/' . $config->logo) }}" alt="logo" height="60">
                    @elseif(file_exists(public_path('logo/logo.jpg')))
                        <img src="{{ public_path('logo/logo.jpg') }}" alt="logo" height="60">
                    @endif
                </td>
                <td class="border-0 text-center" width="50%" style="vertical-align: middle;">
                    <h4 class="text-uppercase invoice-title">
                        {{ $config->business_name ?? 'Fábrica de Bolsas' }}
                    </h4>
                </td>
                <td class="border-0 text-right" width="25%" style="vertical-align: middle;">
                    <h4 class="text-uppercase report-title">
                        PRODUCCIÓN DE BOLSAS
                    </h4>
                    <span style="font-size: 10px; font-weight: bold;">Lote #{{ $production->id }}</span>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Info Box --}}
    <div class="box-details">
        <table class="table border-0" style="margin: 0;">
            <tbody>
                <tr>
                    {{-- Business Info (Left) --}}
                    <td class="border-0 pl-0" width="60%" style="vertical-align: top;">
                        <strong class="text-uppercase" style="font-size: 14px;">{{ $config->business_name ?? '' }}</strong><br>
                        NIT: {{ $config->taxpayer_id ?? '' }}<br>
                        {{ $config->address ?? '' }}<br>
                        Tel: {{ $config->phone ?? '' }}
                    </td>

                    {{-- Report Details (Right) --}}
                    <td class="border-0 text-right pr-0" width="40%" style="vertical-align: top;">
                        Fecha Reporte: <strong>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</strong><br>
                        Fecha Producción: <strong>
                            @php
                                $dates = $production->details->pluck('production_date')->filter()->unique()->sort();
                            @endphp
                            @if($dates->count() > 1)
                                Desde {{ \Carbon\Carbon::parse($dates->first())->format('d/m/Y') }} hasta {{ \Carbon\Carbon::parse($dates->last())->format('d/m/Y') }}
                            @elseif($dates->count() == 1)
                                {{ \Carbon\Carbon::parse($dates->first())->format('d/m/Y') }}
                            @else
                                {{ \Carbon\Carbon::parse($production->production_date)->format('d/m/Y') }}
                            @endif
                        </strong><br>
                        Levantado por: <strong>{{ $production->user->name ?? 'N/A' }}</strong><br>
                        Estado: <strong>{{ $statusText }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($production->note)
        <div class="info" style="margin-bottom: 15px; padding: 5px; background-color: #f8f9fa; border-left: 3px solid #2c3e50;">
            <strong>Observaciones:</strong> {{ $production->note }}
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Producto (Descripción)</th>
                <th class="text-center" width="10%">Cantidad</th>
                <th class="text-center" width="10%">Peso (Kg)</th>
                <th class="text-center" width="16%">Operario / Fabricante</th>
                <th class="text-center" width="12%">Costo</th>
                <th class="text-center" width="12%">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grouped = $production->details->groupBy(function($d) {
                    return $d->production_date ? $d->production_date->format('Y-m-d') : 'Sin Fecha';
                });
            @endphp
            @foreach($grouped as $dateStr => $details)
                @if($dateStr !== 'Sin Fecha')
                    <tr class="bg-light">
                        <td colspan="6" style="font-weight: bold; color: #2c3e50; text-transform: uppercase;">
                            {{ \Carbon\Carbon::parse($dateStr)->locale('es')->isoFormat('dddd DD-MM-YYYY') }}
                        </td>
                    </tr>
                @else
                    <tr class="bg-light">
                        <td colspan="6" style="font-weight: bold; color: #7f8c8d;">
                            Sin Fecha de Producción
                        </td>
                    </tr>
                @endif
                @foreach($details as $detail)
                @php
                    $rowCost = $detail->cost ?? ($detail->product->cost ?? 0);
                    $effQty = ($detail->product->is_variable_quantity) ? $detail->weight : $detail->quantity;
                    $rowTotal = $effQty * $rowCost;
                @endphp
                <tr>
                    <td>
                        {{ $detail->product->name }}
                        @if($detail->product->is_variable_quantity && !empty($detail->metadata))
                            @php
                                $detailRolls = isset($detail->metadata['rolls']) && is_array($detail->metadata['rolls'])
                                    ? $detail->metadata['rolls']
                                    : (isset($detail->metadata['roll']) && is_array($detail->metadata['roll'])
                                        ? [$detail->metadata['roll']]
                                        : (is_array($detail->metadata) ? $detail->metadata : []));
                            @endphp
                            @if(count($detailRolls) > 0)
                                <br>
                                <small style="margin-left: 10px; color: #555;">Desglose de Rollos:</small>
                                <ul style="list-style-type: none; margin: 0; padding-left: 15px; font-size: 9px; color: #555;">
                                    @foreach($detailRolls as $item)
                                        @if(is_array($item))
                                            <li>
                                                Rollo #{{ $loop->iteration }}: Peso: <b>{{ $item['weight'] ?? 0 }} Kg</b> 
                                                @if(!empty($item['color'])) | Color: {{ $item['color'] }} @endif
                                                @if(!empty($item['batch'])) | Lote: {{ $item['batch'] }} @endif
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($detail->quantity, 2) }}</td>
                    <td class="text-center">{{ number_format($detail->weight, 2) }}</td>
                    <td class="text-center">{{ $detail->operator_name }}</td>
                    <td class="text-center">{{ number_format($rowCost, 2) }}</td>
                    <td class="text-center">{{ number_format($rowTotal, 2) }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f8f9fa;">
                <td class="text-right"><strong>Totales:</strong></td>
                <td class="text-center"><strong>{{ number_format($production->details->sum('quantity'), 2) }}</strong></td>
                <td class="text-center"><strong>{{ number_format($production->details->sum('weight'), 2) }}</strong></td>
                <td></td>
                <td></td>
                <td class="text-center"><strong>
                    @php
                        $grandTotal = $production->details->sum(function($d) {
                            $effQty = ($d->product->is_variable_quantity) ? $d->weight : $d->quantity;
                            return $effQty * ($d->cost ?? ($d->product->cost ?? 0));
                        });
                    @endphp
                    {{ number_format($grandTotal, 2) }}
                </strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
