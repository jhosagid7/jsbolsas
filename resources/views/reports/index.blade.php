@extends('layouts.app')
@section('title', 'Reportes de Levantamiento por Día')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">📈 Reporte y Control de Levantamiento de Producción</h3>
        <p class="text-white-50 mb-0">Consolidado por jornadas de producción diaria, operarios, productos y desglose de bobinas.</p>
    </div>
    <a href="{{ route('reports.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger fw-bold shadow-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i> Imprimir / Exportar PDF de Levantamiento
    </a>
</div>

<!-- Filtros Predictivos -->
<div class="card-custom mb-4">
    <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-white-50">Fecha Desde</label>
            <input type="date" name="start_date" class="form-control bg-dark text-white border-secondary" value="{{ request('start_date') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-white-50">Fecha Hasta</label>
            <input type="date" name="end_date" class="form-control bg-dark text-white border-secondary" value="{{ request('end_date') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-white-50">Operario Fabricante</label>
            <select name="user_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos los Operarios</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-white-50">Producto / Medida</label>
            <select name="product_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos los Productos</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100 fw-bold">
                <i class="bi bi-funnel me-1"></i> Filtrar Datos
            </button>
        </div>
    </form>
</div>

<!-- Resumen Financiero y Operativo del Período Filtrado -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom border-start border-info border-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50 fw-bold text-uppercase" style="font-size: 11px;">INGRESOS TOTALES FILTRADOS</small>
                <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                        data-bs-toggle="popover" 
                        data-bs-trigger="hover focus" 
                        data-bs-placement="top" 
                        data-bs-html="true" 
                        data-bs-custom-class="dark-info-popover shadow-lg" 
                        title="ℹ️ Ingresos Totales Filtrados" 
                        data-bs-content="Valor monetario total proyectado por la venta de toda la producción que coincide con los filtros de fecha, operario o producto."
                        tabindex="0"
                        aria-label="Información sobre Ingresos">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <h2 class="text-info fw-bold mb-0 mt-1 fs-3">${{ number_format($financials['total_income'], 2) }}</h2>
            <small class="text-white-50" style="font-size: 11px;">{{ number_format($totalPkgs, 0) }} unids/rollos ({{ number_format($totalKg, 2) }} Kg)</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom border-start border-danger border-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50 fw-bold text-uppercase" style="font-size: 11px;">COSTO TOTAL PRODUCCIÓN</small>
                <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                        data-bs-toggle="popover" 
                        data-bs-trigger="hover focus" 
                        data-bs-placement="top" 
                        data-bs-html="true" 
                        data-bs-custom-class="dark-info-popover shadow-lg" 
                        title="ℹ️ Costo Total de Producción" 
                        data-bs-content="Suma de materia prima (plástico $/KG) más los costos fijos acumulados en los {{ $financials['total_shifts'] ?? 0 }} turnos filtrados."
                        tabindex="0"
                        aria-label="Información sobre Costo Total">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <h2 class="text-danger fw-bold mb-0 mt-1 fs-3">${{ number_format($financials['total_cost'], 2) }}</h2>
            <small class="text-white-50" style="font-size: 11px;">Mat. Prima: ${{ number_format($financials['total_raw_cost'], 2) }} • Fijo: ${{ number_format($financials['total_fixed_cost'], 2) }}</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom border-start border-{{ $financials['net_profit'] >= 0 ? 'success' : 'danger' }} border-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50 fw-bold text-uppercase" style="font-size: 11px;">UTILIDAD NETA REAL</small>
                <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                        data-bs-toggle="popover" 
                        data-bs-trigger="hover focus" 
                        data-bs-placement="top" 
                        data-bs-html="true" 
                        data-bs-custom-class="dark-info-popover shadow-lg" 
                        title="ℹ️ Utilidad Neta Real" 
                        data-bs-content="Ganancia neta final generada en el período filtrado tras pagar plástico y costos fijos de planta."
                        tabindex="0"
                        aria-label="Información sobre Utilidad Neta">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <h2 class="text-{{ $financials['net_profit'] >= 0 ? 'success' : 'danger' }} fw-bold mb-0 mt-1 fs-3">
                ${{ number_format($financials['net_profit'], 2) }}
            </h2>
            <small class="badge bg-{{ $financials['net_profit'] >= 0 ? 'success' : 'danger' }} text-white">
                Margen Real: {{ $financials['margin_percent'] }}%
            </small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom border-start border-warning border-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50 fw-bold text-uppercase" style="font-size: 11px;">VOLUMEN FÍSICO TOTAL</small>
                <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                        data-bs-toggle="popover" 
                        data-bs-trigger="hover focus" 
                        data-bs-placement="top" 
                        data-bs-html="true" 
                        data-bs-custom-class="dark-info-popover shadow-lg" 
                        title="ℹ️ Volumen Físico Total" 
                        data-bs-content="Kilos totales y unidades fabricadas en las {{ $groupedByDay->count() }} jornadas de trabajo filtradas."
                        tabindex="0"
                        aria-label="Información sobre Volumen">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <h2 class="text-warning fw-bold mb-0 mt-1 fs-3">{{ number_format($totalKg, 2) }} Kg</h2>
            <small class="text-white-50" style="font-size: 11px;">{{ number_format($totalPkgs, 0) }} unids. en {{ $groupedByDay->count() }} días</small>
        </div>
    </div>
</div>

<!-- Levantamientos Agrupados por Día (Estilo JSPOS) -->
@if($groupedByDay->isEmpty())
    <div class="card-custom text-center py-5">
        <i class="bi bi-inbox fs-1 text-white-50 d-block mb-2"></i>
        <p class="text-white-50 mb-0">No se encontraron registros de producción para los filtros seleccionados.</p>
    </div>
@else
    @foreach($groupedByDay as $dateStr => $items)
        @php
            $dayTotalKg = $items->sum('weight');
            $dayTotalQty = $items->sum('quantity');
            $formattedDate = $dateStr !== 'Sin Fecha' 
                ? \Carbon\Carbon::parse($dateStr)->locale('es')->isoFormat('dddd DD [de] MMMM [de] YYYY')
                : 'Sin Fecha de Registro';
        @endphp
        <div class="card-custom mb-4 p-0 overflow-hidden">
            <!-- Header del Día -->
            <div class="p-3 bg-secondary-subtle border-bottom border-secondary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <span class="fs-5 me-2">📅</span>
                    <h5 class="fw-bold mb-0 text-uppercase text-white">{{ $formattedDate }}</h5>
                </div>
                <div class="d-flex gap-3">
                    <span class="badge bg-dark text-info fs-6 border border-info-subtle">
                        📦 Total Cantidad: <strong>{{ number_format($dayTotalQty, 0) }}</strong>
                    </span>
                    <span class="badge bg-dark text-success fs-6 border border-success-subtle">
                        ⚖️ Peso del Día: <strong>{{ number_format($dayTotalKg, 2) }} Kg</strong>
                    </span>
                </div>
            </div>

            <!-- Tabla de Detalles de la Jornada -->
            <div class="table-responsive">
                <table class="table table-custom mb-0 align-middle">
                    <thead>
                        <tr>
                            <th width="15%">Hora</th>
                            <th width="20%">Operario</th>
                            <th width="25%">Producto / Medida</th>
                            <th width="12%">Cantidad</th>
                            <th width="13%">Peso Real</th>
                            <th width="15%">Estado / QR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $p)
                            @php
                                $isRoll = ($p->product && $p->product->is_variable_quantity) || 
                                          str_contains(strtoupper($p->product->name ?? ''), 'BOBINA') || 
                                          !empty($p->metadata);
                            @endphp
                            <tr>
                                <td>
                                    <span class="text-white-50">{{ $p->recorded_at ? $p->recorded_at->format('h:i A') : '-' }}</span>
                                    <br><small class="text-muted">{{ strtoupper($p->shift->shift_type ?? 'Diurno') }}</small>
                                </td>
                                <td class="fw-bold text-white">
                                    {{ $p->user->name ?? 'Operario' }}
                                </td>
                                <td>
                                    <span class="fw-bold text-info">{{ $p->product->name ?? 'Producto' }}</span>
                                    @if($p->product && $p->product->sku)
                                        <br><small class="text-white-50">SKU: {{ $p->product->sku }}</small>
                                    @endif

                                    <!-- Desglose de Bobinas / Rollos -->
                                    @if(!empty($p->metadata) && is_array($p->metadata))
                                        @php
                                            $pRolls = isset($p->metadata['rolls']) && is_array($p->metadata['rolls'])
                                                ? $p->metadata['rolls']
                                                : (isset($p->metadata['roll']) && is_array($p->metadata['roll'])
                                                    ? [$p->metadata['roll']]
                                                    : (is_array($p->metadata) ? $p->metadata : []));
                                        @endphp
                                        @if(count($pRolls) > 0)
                                            <div class="mt-1 p-2 bg-dark rounded border border-secondary" style="font-size: 0.75rem;">
                                                <span class="text-warning fw-bold d-block mb-1">🔄 Desglose de {{ count($pRolls) }} Rollos:</span>
                                                @foreach($pRolls as $r)
                                                    @if(is_array($r))
                                                        <span class="badge bg-secondary me-1 mb-1">
                                                            #{{ $loop->iteration }}: {{ $r['weight'] ?? 0 }} Kg
                                                            @if(!empty($r['color'])) ({{ $r['color'] }}) @endif
                                                            @if(!empty($r['batch'])) [L:{{ $r['batch'] }}] @endif
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $isRoll ? 'bg-warning text-dark' : 'bg-primary' }}">
                                        {{ number_format($p->quantity, 0) }} {{ $isRoll ? 'Rollos' : 'Bultos' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-success fw-bold fs-6">{{ number_format($p->weight, 2) }} Kg</span>
                                    @if($p->original_weight && (float)$p->original_weight !== (float)$p->weight)
                                        <br><small class="text-muted"><del>{{ number_format($p->original_weight, 2) }} Kg</del></small>
                                    @endif
                                </td>
                                <td>
                                    @if($p->status === 'approved')
                                        <span class="badge bg-success">✅ Aprobado</span>
                                    @elseif($p->status === 'lifted')
                                        <span class="badge bg-info text-dark">📦 En Almacén POS</span>
                                    @elseif($p->status === 'pending_review')
                                        <span class="badge bg-warning text-dark">⏳ En Báscula</span>
                                    @elseif($p->status === 'rejected')
                                        <span class="badge bg-danger">❌ Rechazado</span>
                                    @endif
                                    @if($p->qr_code)
                                        <br><span class="badge bg-dark text-white-50 font-monospace mt-1" style="font-size: 0.7rem;">{{ $p->qr_code }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endif
@endsection
