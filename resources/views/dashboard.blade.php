@extends('layouts.app')
@section('title', 'Monitor en Vivo y Rendimiento')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-1">📊 Monitor de Producción & Finanzas</h3>
            <span class="badge bg-primary text-white font-monospace px-2 py-1" style="font-size: 11px;">
                {{ $financials['period_label'] }}
            </span>
        </div>
        <p class="text-white-50 mb-0">Control en tiempo real de metas de trabajadores, pesaje en báscula y balance financiero acumulado.</p>
    </div>

    <!-- Selector de Período -->
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="btn-group p-1 bg-dark border border-secondary rounded-pill shadow-sm" role="group">
            <a href="{{ route('dashboard', ['period' => 'today']) }}" 
               class="btn btn-sm rounded-pill fw-bold {{ ($financials['period'] ?? 'today') === 'today' ? 'btn-primary text-white shadow' : 'btn-link text-white-50 text-decoration-none' }}">
                ☀️ Hoy
            </a>
            <a href="{{ route('dashboard', ['period' => 'week']) }}" 
               class="btn btn-sm rounded-pill fw-bold {{ ($financials['period'] ?? '') === 'week' ? 'btn-primary text-white shadow' : 'btn-link text-white-50 text-decoration-none' }}">
                📅 Esta Semana
            </a>
            <a href="{{ route('dashboard', ['period' => 'month']) }}" 
               class="btn btn-sm rounded-pill fw-bold {{ ($financials['period'] ?? '') === 'month' ? 'btn-primary text-white shadow' : 'btn-link text-white-50 text-decoration-none' }}">
                🗓️ Este Mes
            </a>
            <button type="button" 
                    class="btn btn-sm rounded-pill fw-bold {{ ($financials['period'] ?? '') === 'custom' ? 'btn-warning text-dark shadow' : 'btn-link text-white-50 text-decoration-none' }}" 
                    data-bs-toggle="modal" 
                    data-bs-target="#customRangeModal">
                🔍 Rango Libre
            </button>
        </div>

        <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center gap-1">
            @if(request('period'))
                <input type="hidden" name="period" value="{{ request('period') }}">
            @endif
            @if(request('start_date'))
                <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                <input type="hidden" name="end_date" value="{{ request('end_date') }}">
            @endif
            <select name="machine_id" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()" style="min-width: 170px;">
                <option value="">⚙️ Todas las Máquinas</option>
                @foreach($allMachines as $m)
                    <option value="{{ $m->id }}" {{ ($selectedMachine?->id ?? '') == $m->id ? 'selected' : '' }}>
                        {{ $m->name }} ({{ $m->code }})
                    </option>
                @endforeach
            </select>
            @if($selectedMachine)
                <a href="{{ route('dashboard', array_filter(['period' => request('period'), 'start_date' => request('start_date'), 'end_date' => request('end_date')])) }}" class="btn btn-outline-secondary btn-sm" title="Limpiar filtro máquina">
                    <i class="bi bi-x"></i>
                </a>
            @endif
        </form>

        <a href="{{ route('reports.index') }}" class="btn btn-outline-success btn-sm fw-bold">
            <i class="bi bi-journal-text me-1"></i> Reportes & PDF
        </a>
    </div>
</div>

@if($machineStats)
<!-- Panel de Rendimiento de Máquina Seleccionada -->
<div class="card-custom mb-4 border border-info border-opacity-50">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-info mb-0">
            <i class="bi bi-cpu-fill text-info me-2"></i> {{ $machineStats['machine']->name }} ({{ $machineStats['machine']->code }}) &bull; EFICIENCIA DE TURNO
        </h5>
        <span class="badge bg-info text-dark font-monospace fw-bold px-3 py-1">
            Eficiencia: {{ $machineStats['efficiency'] }}%
        </span>
    </div>
    <div class="row g-3 text-center">
        <div class="col-6 col-md-3">
            <div class="bg-dark p-2 rounded border border-secondary border-opacity-25">
                <small class="text-white-50 d-block">Peso Total</small>
                <strong class="text-white fs-6">{{ number_format($machineStats['total_kg'], 2) }} Kg</strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-dark p-2 rounded border border-secondary border-opacity-25">
                <small class="text-white-50 d-block">Paquetes / Bultos</small>
                <strong class="text-white fs-6">{{ number_format($machineStats['total_packages'], 0) }} unids.</strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-dark p-2 rounded border border-secondary border-opacity-25">
                <small class="text-white-50 d-block">Horas Estimadas</small>
                <strong class="text-white fs-6">{{ $machineStats['estimated_hours'] }} hrs</strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-dark p-2 rounded border border-secondary border-opacity-25">
                <small class="text-white-50 d-block">Jornadas / Turnos</small>
                <strong class="text-white fs-6">{{ $machineStats['shifts_count'] }}</strong>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Modal para Rango de Fechas Personalizado -->
<div class="modal fade" id="customRangeModal" tabindex="-1" aria-labelledby="customRangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold" id="customRangeModalLabel">
                    <i class="bi bi-calendar-range text-warning me-2"></i> Consultar Rango de Fechas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('dashboard') }}" method="GET">
                <input type="hidden" name="period" value="custom">
                <div class="modal-body">
                    <p class="text-white-50 small mb-3">
                        Selecciona el rango de fechas para recalcular el balance de ingresos, costos de producción y utilidades del período.
                    </p>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small text-white-50 fw-bold">Fecha Desde</label>
                            <input type="date" name="start_date" class="form-control bg-dark text-white border-secondary" value="{{ $financials['start_date'] ?? date('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-white-50 fw-bold">Fecha Hasta</label>
                            <input type="date" name="end_date" class="form-control bg-dark text-white border-secondary" value="{{ $financials['end_date'] ?? date('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold">
                        <i class="bi bi-search me-1"></i> Filtrar Monitor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- KPIs Financieros en Tiempo Real -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom border-start border-info border-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50 fw-bold text-uppercase" style="font-size: 11px;">
                    INGRESOS ({{ ($financials['period'] ?? 'today') === 'today' ? 'HOY' : (($financials['period'] ?? '') === 'week' ? 'SEMANA' : (($financials['period'] ?? '') === 'month' ? 'MES' : 'PERÍODO')) }})
                </small>
                <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                        data-bs-toggle="popover" 
                        data-bs-trigger="hover focus" 
                        data-bs-placement="top" 
                        data-bs-html="true" 
                        data-bs-custom-class="dark-info-popover shadow-lg" 
                        title="ℹ️ Ingresos Proyectados" 
                        data-bs-content="Representa el valor monetario estimado de venta de todas las bolsas y bobinas fabricadas en el período seleccionado ({{ $financials['period_label'] }}).<br><br><strong>¿Cómo se calcula?</strong> Multiplica la cantidad de bultos o kilos pesados por el precio de salida de fábrica de cada producto."
                        tabindex="0"
                        aria-label="Información sobre Ingresos Proyectados">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <h2 class="text-info fw-bold mb-0 mt-1 fs-3">${{ number_format($financials['today_income'], 2) }}</h2>
            <small class="text-white-50" style="font-size: 11px;">{{ number_format($stats['today_packages'], 0) }} unids/rollos ({{ number_format($stats['today_kg'], 2) }} Kg)</small>
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
                        data-bs-content="Suma de todos los costos necesarios para fabricar la producción en este período.<br><br><strong>¿Cómo se compone?</strong><br>• <strong>Materia Prima:</strong> Costo del plástico según su fórmula de mezcla ($/KG).<br>• <strong>Costo Fijo:</strong> Gastos de luz y sueldo acumulados por cada turno activo de máquina."
                        tabindex="0"
                        aria-label="Información sobre Costo Total de Producción">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <h2 class="text-danger fw-bold mb-0 mt-1 fs-3">${{ number_format($financials['today_cost'], 2) }}</h2>
            <small class="text-white-50" style="font-size: 11px;">Mat. Prima: ${{ number_format($financials['today_raw_cost'], 2) }} • Fijo: ${{ number_format($financials['today_fixed_cost'], 2) }}</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom border-start border-{{ $financials['today_net_profit'] >= 0 ? 'success' : 'danger' }} border-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50 fw-bold text-uppercase" style="font-size: 11px;">
                    UTILIDAD NETA ({{ ($financials['period'] ?? 'today') === 'today' ? 'HOY' : (($financials['period'] ?? '') === 'week' ? 'SEMANA' : (($financials['period'] ?? '') === 'month' ? 'MES' : 'PERÍODO')) }})
                </small>
                <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                        data-bs-toggle="popover" 
                        data-bs-trigger="hover focus" 
                        data-bs-placement="top" 
                        data-bs-html="true" 
                        data-bs-custom-class="dark-info-popover shadow-lg" 
                        title="ℹ️ Utilidad Neta del Período" 
                        data-bs-content="Es la ganancia limpia y real generada en este período ({{ $financials['period_label'] }}) tras descontar los costos del plástico y los costos fijos acumulados.<br><br><strong>Margen Real:</strong> Porcentaje de rentabilidad neta sobre las ventas totales."
                        tabindex="0"
                        aria-label="Información sobre Utilidad Neta">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <h2 class="text-{{ $financials['today_net_profit'] >= 0 ? 'success' : 'danger' }} fw-bold mb-0 mt-1 fs-3">
                ${{ number_format($financials['today_net_profit'], 2) }}
            </h2>
            <small class="badge bg-{{ $financials['today_net_profit'] >= 0 ? 'success' : 'danger' }} text-white">
                Margen Real: {{ $financials['today_margin_percent'] }}%
            </small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom border-start border-warning border-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50 fw-bold text-uppercase" style="font-size: 11px;">{{ strtoupper($financials['target_title'] ?? 'META UTILIDAD') }}</small>
                <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                        data-bs-toggle="popover" 
                        data-bs-trigger="hover focus" 
                        data-bs-placement="top" 
                        data-bs-html="true" 
                        data-bs-custom-class="dark-info-popover shadow-lg" 
                        title="ℹ️ {{ $financials['target_title'] ?? 'Meta Utilidad' }}" 
                        data-bs-content="Objetivo económico de ganancia neta para este período ({{ $financials['days_count'] ?? 1 }} días a ${{ number_format($settings->daily_profit_target, 2) }}/día = ${{ number_format($financials['daily_target'], 2) }} USD).<br><br><strong>Barra de Progreso:</strong> Muestra qué porcentaje de la meta ya se ha alcanzado."
                        tabindex="0"
                        aria-label="Información sobre Meta Utilidad">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <h2 class="text-warning fw-bold mb-0 mt-1 fs-3">${{ number_format($financials['daily_target'], 2) }}</h2>
            <div class="progress mt-2" style="height: 8px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ min(100, $financials['target_profit_percent']) }}%;"></div>
            </div>
            <small class="text-white-50 d-block mt-1" style="font-size: 11px;">{{ $financials['target_profit_percent'] }}% alcanzado</small>
        </div>
    </div>
</div>

<!-- Rendimiento de Trabajadores y Turnos en Planta -->
<div class="card-custom mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-white mb-0">
            <i class="bi bi-person-check-fill text-info me-2"></i> Evaluación de Rendimiento por Trabajador y Turno
        </h5>
        <span class="badge bg-dark text-warning border border-warning-subtle">
            ⚡ {{ count($activeShiftsList) }} Jornadas ({{ $financials['period_label'] }})
        </span>
    </div>

    @if($activeShiftsList->isEmpty())
        <div class="p-4 text-center text-white-50">
            No hay turnos registrados para el período seleccionado ({{ $financials['period_label'] }}).
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Operario</th>
                        <th>Turno</th>
                        <th>
                            Meta Asignada
                            <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none ms-1" 
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="hover focus" 
                                    data-bs-placement="top" 
                                    data-bs-html="true" 
                                    data-bs-custom-class="dark-info-popover shadow-lg" 
                                    title="ℹ️ Meta Asignada" 
                                    data-bs-content="Cantidad de bultos o bobinas que el operario debe producir en su turno de trabajo según la ficha técnica de los productos elaborados."
                                    tabindex="0"
                                    aria-label="Información sobre Meta Asignada">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>
                        </th>
                        <th>
                            Producción Real
                            <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none ms-1" 
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="hover focus" 
                                    data-bs-placement="top" 
                                    data-bs-html="true" 
                                    data-bs-custom-class="dark-info-popover shadow-lg" 
                                    title="ℹ️ Producción Real" 
                                    data-bs-content="Cantidad física exacta de unidades y kilos pesados y sincronizados desde la aplicación móvil durante ese turno."
                                    tabindex="0"
                                    aria-label="Información sobre Producción Real">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>
                        </th>
                        <th width="25%">
                            % Cumplimiento Meta
                            <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none ms-1" 
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="hover focus" 
                                    data-bs-placement="top" 
                                    data-bs-html="true" 
                                    data-bs-custom-class="dark-info-popover shadow-lg" 
                                    title="ℹ️ Cumplimiento de Meta" 
                                    data-bs-content="Porcentaje de avance del trabajador frente a su meta asignada.<br><br>• <strong class='text-success'>Verde:</strong> Meta cumplida (≥100%)<br>• <strong class='text-warning'>Amarillo:</strong> Avance medio (60% a 99%)<br>• <strong class='text-danger'>Rojo:</strong> Bajo rendimiento (<60%)"
                                    tabindex="0"
                                    aria-label="Información sobre Cumplimiento de Meta">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>
                        </th>
                        <th>Evaluación de Meta</th>
                        <th>
                            Utilidad Neta Real
                            <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none ms-1" 
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="hover focus" 
                                    data-bs-placement="top" 
                                    data-bs-html="true" 
                                    data-bs-custom-class="dark-info-popover shadow-lg" 
                                    title="ℹ️ Utilidad Neta Real del Turno" 
                                    data-bs-content="Ganancia neta generada exclusivamente por ese turno de trabajo (Ingresos por venta del turno menos el costo del plástico utilizado y menos el costo fijo operativo del turno)."
                                    tabindex="0"
                                    aria-label="Información sobre Utilidad Neta Real del Turno">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeShiftsList as $shift)
                        @php
                            $target = (float)($shift->target_packages > 0 ? $shift->target_packages : 5.0);
                            $actual = (float)$shift->total_packages;
                            $pct = $target > 0 ? round(($actual / $target) * 100.0, 1) : 100.0;
                            $isMet = $actual >= $target;
                            $net = (float)$shift->net_profit;
                        @endphp
                        <tr>
                            <td class="fw-bold text-white">
                                <i class="bi bi-person-circle text-info me-1"></i> {{ $shift->user->name ?? 'Operario' }}
                            </td>
                            <td>
                                @if($shift->shift_type === 'diurno')
                                    <span class="badge bg-primary">☀️ Diurno</span>
                                @else
                                    <span class="badge bg-secondary">🌙 Nocturno</span>
                                @endif
                                <br><small class="text-white-50">{{ $shift->start_time ? $shift->start_time->format('h:i A') : '' }}</small>
                            </td>
                            <td>
                                <strong class="text-warning">{{ number_format($target, 0) }} unids.</strong>
                            </td>
                            <td>
                                <strong class="text-info fs-6">{{ number_format($actual, 0) }} unids.</strong>
                                <br><small class="text-white-50">({{ number_format($shift->total_weight, 2) }} Kg)</small>
                            </td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-bold {{ $isMet ? 'text-success' : ($pct >= 60 ? 'text-warning' : 'text-danger') }}">
                                        {{ $pct }}% de la meta
                                    </small>
                                </div>
                                <div class="progress" style="height: 10px; background-color: #0f172a;">
                                    <div class="progress-bar {{ $isMet ? 'bg-success' : ($pct >= 60 ? 'bg-warning' : 'bg-danger') }}" 
                                         role="progressbar" 
                                         style="width: {{ min(100, $pct) }}%;">
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($isMet)
                                    <span class="badge bg-success fw-bold px-3 py-1 shadow-sm">
                                        <i class="bi bi-check-circle me-1"></i> META ALCANZADA
                                    </span>
                                @else
                                    <span class="badge bg-danger fw-bold px-3 py-1 shadow-sm">
                                        <i class="bi bi-x-circle me-1"></i> META NO ALCANZADA
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold text-{{ $net >= 0 ? 'success' : 'danger' }} fs-6 font-monospace">
                                    ${{ number_format($net, 2) }}
                                </span>
                                <br><small class="text-white-50">Margen: {{ $shift->profit_margin_percent ?? 0 }}%</small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Accesos Rápidos -->
<div class="row g-3">
    <div class="col-md-3">
        <div class="card-custom h-100">
            <h6 class="fw-bold mb-2"><i class="bi bi-sliders text-info me-2"></i> Costos & Precios</h6>
            <p class="text-white-50 small mb-3">Simula el precio de fábrica para alcanzar metas de utilidad diaria.</p>
            <a href="{{ route('costs.index') }}" class="btn btn-info btn-sm fw-bold w-100">Simulador de Costos</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom h-100">
            <h6 class="fw-bold mb-2"><i class="bi bi-bezier2 text-warning me-2"></i> Fórmulas de Mezcla</h6>
            <p class="text-white-50 small mb-3">Recetas y costo promedio ponderado $/KG por tipo de bolsa.</p>
            <a href="{{ route('formulas.index') }}" class="btn btn-warning btn-sm fw-bold w-100">Ver Fórmulas</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom h-100">
            <h6 class="fw-bold mb-2"><i class="bi bi-speedometer text-warning me-2"></i> Báscula & Auditoría</h6>
            <p class="text-white-50 small mb-3">Revisa pesajes reales, rollos individuales y aprueba para almacén.</p>
            <a href="{{ route('scale.index') }}" class="btn btn-warning btn-sm fw-bold w-100">Ir a la Báscula</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom h-100">
            <h6 class="fw-bold mb-2"><i class="bi bi-journal-text text-success me-2"></i> Reportes por Día</h6>
            <p class="text-white-50 small mb-3">Reporte oficial agrupado por día con exportación a PDF formal.</p>
            <a href="{{ route('reports.index') }}" class="btn btn-success btn-sm fw-bold w-100">Ver Reportes</a>
        </div>
    </div>
</div>
@endsection
