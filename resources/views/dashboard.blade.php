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
            <span id="livePulseBadge" class="badge bg-dark text-success border border-success border-opacity-50 font-monospace px-2 py-1" style="font-size: 10px;" title="Sincronización reactiva en tiempo real">
                <span class="spinner-grow spinner-grow-sm text-success me-1" style="width: 7px; height: 7px;" role="status"></span> EN VIVO
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

        <a href="{{ route('operator.station') }}" class="btn btn-warning btn-sm fw-bold text-dark shadow-sm">
            <i class="bi bi-lightning-charge-fill me-1"></i> ⚡ Estación de Operarios
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

<!-- Toast / Banner de Sincronización en Vivo -->
<div id="liveSyncToast" class="alert alert-success d-none py-2 px-3 mb-3 border-0 shadow-lg text-white d-flex align-items-center justify-content-between" style="background: linear-gradient(90deg, #059669, #10b981); border-radius: 12px;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-broadcast fs-5"></i>
        <span id="liveSyncToastText" class="fw-bold small">¡Nuevo pesaje sincronizado desde la app móvil! Datos actualizados en tiempo real.</span>
    </div>
    <button type="button" class="btn-close btn-close-white btn-sm" onclick="document.getElementById('liveSyncToast').classList.add('d-none')"></button>
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
            <h2 class="text-info fw-bold mb-0 mt-1 fs-3">$<span id="kpi_today_income">{{ number_format($financials['today_income'], 2) }}</span></h2>
            <small class="text-white-50" style="font-size: 11px;" id="kpi_today_packages_kg">{{ number_format($stats['today_packages'], 0) }} unids/rollos ({{ number_format($stats['today_kg'], 2) }} Kg)</small>
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
            <h2 class="text-danger fw-bold mb-0 mt-1 fs-3">$<span id="kpi_today_cost">{{ number_format($financials['today_cost'], 2) }}</span></h2>
            <small class="text-white-50" style="font-size: 11px;" id="kpi_today_cost_breakdown">Mat. Prima: ${{ number_format($financials['today_raw_cost'], 2) }} • Fijo: ${{ number_format($financials['today_fixed_cost'], 2) }}</small>
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
            <h2 class="text-{{ $financials['today_net_profit'] >= 0 ? 'success' : 'danger' }} fw-bold mb-0 mt-1 fs-3" id="kpi_today_net_profit_wrapper">
                $<span id="kpi_today_net_profit">{{ number_format($financials['today_net_profit'], 2) }}</span>
            </h2>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <small class="badge bg-{{ $financials['today_net_profit'] >= 0 ? 'success' : 'danger' }} text-white" id="kpi_today_margin_badge">
                    Margen: {{ $financials['today_margin_percent'] }}%
                </small>
                <small class="text-white-50" style="font-size: 10px;" id="kpi_today_meta_fin" title="Meta de Utilidad Económica">
                    Meta Fin: ${{ number_format($financials['daily_target'], 0) }} ({{ $financials['target_profit_percent'] }}%)
                </small>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom border-start border-warning border-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-white-50 fw-bold text-uppercase" style="font-size: 11px;">META PRODUCCIÓN PLANTA</small>
                <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                        data-bs-toggle="popover" 
                        data-bs-trigger="hover focus" 
                        data-bs-placement="top" 
                        data-bs-html="true" 
                        data-bs-custom-class="dark-info-popover shadow-lg" 
                        title="ℹ️ Meta Global de Producción de Planta" 
                        data-bs-content="Representa la meta física total de la fábrica para este período (suma de las metas asignadas a todos los turnos abiertos en planta: <strong>{{ number_format($financials['factory_target_packages'], 0) }} unids</strong>).<br><br><strong>¿Cómo se evalúa?</strong> La fábrica completa el 100% de su meta de producción únicamente cuando la producción física acumulada de todos los trabajadores alcanza la cuota total exigida."
                        tabindex="0"
                        aria-label="Información sobre Meta de Producción de Planta">
                    <i class="bi bi-info-circle-fill"></i>
                </button>
            </div>
            <h2 class="text-warning fw-bold mb-0 mt-1 fs-4" id="kpi_factory_real_target">
                <span id="kpi_factory_real_pkgs">{{ number_format($financials['factory_real_packages'], 0) }}</span> 
                <span class="text-white-50 fs-6">/ <span id="kpi_factory_target_pkgs">{{ number_format($financials['factory_target_packages'], 0) }}</span> unids</span>
            </h2>
            <div class="progress mt-2" style="height: 8px; background-color: #0f172a;">
                <div id="kpi_factory_progress_bar" class="progress-bar {{ $financials['is_factory_target_met'] ? 'bg-success' : ($financials['factory_target_progress_percent'] >= 60 ? 'bg-warning' : 'bg-danger') }}" 
                     role="progressbar" 
                     style="width: {{ min(100, $financials['factory_target_progress_percent']) }}%;"></div>
            </div>
            <div id="kpi_factory_subtext">
                @if(($financials['factory_target_packages'] ?? 0) == 0)
                    <small class="text-white-50 d-block mt-1" style="font-size: 11px;">Sin metas asignadas en el período</small>
                @elseif($financials['is_factory_target_met'])
                    <small class="text-success fw-bold d-block mt-1" style="font-size: 11px;">
                        <i class="bi bi-check-circle-fill me-1"></i> 100% - META DE FÁBRICA CUMPLIDA ({{ $financials['met_operators_count'] }}/{{ $financials['active_operators_count'] }})
                    </small>
                @else
                    <small class="text-white-50 d-block mt-1" style="font-size: 11px;">
                        <span class="text-warning fw-bold">{{ $financials['factory_target_progress_percent'] }}% alcanzado</span> &bull; {{ $financials['met_operators_count'] }}/{{ $financials['active_operators_count'] }} operarios al 100%
                    </small>
                @endif
            </div>
            <div class="mt-2 pt-1 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-1" style="font-size: 11px;">
                <span id="kpi_plant_status_badge" class="badge {{ $financials['plant_status'] === 'open' ? 'bg-success' : ($financials['plant_status'] === 'closed' ? 'bg-secondary' : 'bg-dark') }} text-white">
                    {{ $financials['plant_status_label'] }}
                </span>
                <span id="kpi_plant_time_info" class="text-white-50" title="Horario de Planta & Horas-Hombre Acumuladas">
                    <i class="bi bi-clock-history text-info me-1"></i>{{ $financials['plant_schedule_window'] }} &bull; 👥 {{ $financials['plant_man_hours_human'] }}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Rendimiento de Trabajadores y Turnos en Planta -->
<div class="card-custom mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-white mb-0">
            <i class="bi bi-person-check-fill text-info me-2"></i> Evaluación de Rendimiento por Trabajador y Turno
        </h5>
        <span id="table_jornadas_badge" class="badge bg-dark text-warning border border-warning-subtle">
            ⚡ {{ count($activeShiftsList) }} Jornadas &bull; {{ $financials['met_operators_count'] }}/{{ $financials['active_operators_count'] }} Operarios al 100%
        </span>
    </div>

    <div id="empty_shifts_notice" class="p-4 text-center text-white-50 {{ $activeShiftsList->isEmpty() ? '' : 'd-none' }}">
        No hay turnos registrados para el período seleccionado ({{ $financials['period_label'] }}).
    </div>

    <div id="shifts_table_wrapper" class="table-responsive {{ $activeShiftsList->isEmpty() ? 'd-none' : '' }}">
        <table class="table table-custom mb-0 align-middle">
            <thead>
                <tr>
                    <th>Operario</th>
                    <th>Turno & Estado</th>
                    <th>
                        Horario & Tiempo Laborado
                        <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none ms-1" 
                                data-bs-toggle="popover" 
                                data-bs-trigger="hover focus" 
                                data-bs-placement="top" 
                                data-bs-html="true" 
                                data-bs-custom-class="dark-info-popover shadow-lg" 
                                title="ℹ️ Tiempo Laborado" 
                                data-bs-content="Hora exacta de apertura y cierre del turno del operario, junto con el total de horas efectivas trabajadas."
                                tabindex="0"
                                aria-label="Información sobre Horario y Tiempo">
                            <i class="bi bi-info-circle-fill"></i>
                        </button>
                    </th>
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
                    <th width="20%">
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
            <tbody id="active_shifts_tbody">
                @foreach($activeShiftsList as $shift)
                    @php
                        $target = (float)($shift->target_packages > 0 ? $shift->target_packages : 5.0);
                        $actual = (float)$shift->total_packages;
                        $pct = $shift->combined_progress_percent ?: ($target > 0 ? round(($actual / $target) * 100.0, 1) : 0.0);
                        $isMet = $pct >= 100.0;
                        $net = (float)$shift->net_profit;
                    @endphp
                    <tr>
                        <td class="fw-bold text-white">
                            <i class="bi bi-person-circle text-info me-1"></i> {{ $shift->effective_user->name ?? $shift->user->name ?? 'Operario' }}
                            @if($shift->machine)
                                <br><small class="badge bg-dark text-info border border-secondary mt-1">{{ $shift->machine->name }} ({{ $shift->machine->code }})</small>
                            @endif
                        </td>
                        <td>
                            @if($shift->shift_type === 'diurno')
                                <span class="badge bg-primary">☀️ Diurno</span>
                            @else
                                <span class="badge bg-secondary">🌙 Nocturno</span>
                            @endif
                            @if($shift->status === 'open')
                                <span class="badge bg-success text-white" style="font-size: 10px;">🟢 Abierto</span>
                            @else
                                <span class="badge bg-dark text-white-50 border border-secondary" style="font-size: 10px;">⚪ Cerrado</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-white">{{ $shift->start_time_human }} - {{ $shift->end_time_human }}</span>
                            <br><small class="text-info fw-bold"><i class="bi bi-stopwatch me-1"></i>{{ $shift->duration_human }}</small>
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
                                    {{ $pct }}% de su meta
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
                            @elseif($shift->status === 'open')
                                <span class="badge bg-warning text-dark fw-bold px-3 py-1 shadow-sm">
                                    <i class="bi bi-hourglass-split me-1"></i> EN PROCESO (Faltan {{ max(0, (int)($target - $actual)) }})
                                </span>
                            @else
                                <span class="badge bg-danger fw-bold px-3 py-1 shadow-sm">
                                    <i class="bi bi-x-circle me-1"></i> NO ALCANZADA
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
</div>

<!-- Accesos Rápidos -->
<div class="row g-3 mb-4">
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let latestProdId = {{ (int)(\App\Models\BagProduction::max('id') ?? 0) }};
        let isPolling = false;
        const currentParams = new URLSearchParams(window.location.search);
        const liveDataUrl = "{{ route('dashboard.live_data') }}?" + currentParams.toString();

        async function fetchDashboardUpdates() {
            if (document.visibilityState !== 'visible' || isPolling) return;
            isPolling = true;

            try {
                const res = await fetch(liveDataUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) return;
                const data = await res.json();

                if (data && data.success) {
                    updateDashboardDOM(data);

                    if (data.latest_production_id && data.latest_production_id > latestProdId) {
                        latestProdId = data.latest_production_id;
                        showLiveSyncToast("⚡ ¡Nuevo pesaje registrado en planta! Métricas actualizadas en vivo.");
                    }
                }
            } catch (err) {
                // Silencioso en caso de pérdida momentánea de red
            } finally {
                isPolling = false;
            }
        }

        function updateDashboardDOM(data) {
            const fin = data.financials;

            // 1. KPIs
            const incEl = document.getElementById('kpi_today_income');
            if (incEl) incEl.textContent = fin.today_income_formatted;

            const costEl = document.getElementById('kpi_today_cost');
            if (costEl) costEl.textContent = fin.today_cost_formatted;

            const costBreakEl = document.getElementById('kpi_today_cost_breakdown');
            if (costBreakEl) costBreakEl.textContent = `Mat. Prima: $${fin.today_raw_cost_formatted} • Fijo: $${fin.today_fixed_cost_formatted}`;

            const netEl = document.getElementById('kpi_today_net_profit');
            if (netEl) netEl.textContent = fin.today_net_profit_formatted;

            const netWrap = document.getElementById('kpi_today_net_profit_wrapper');
            if (netWrap) {
                netWrap.className = `text-${fin.today_net_profit >= 0 ? 'success' : 'danger'} fw-bold mb-0 mt-1 fs-3`;
            }

            const marginBadge = document.getElementById('kpi_today_margin_badge');
            if (marginBadge) {
                marginBadge.className = `badge bg-${fin.today_net_profit >= 0 ? 'success' : 'danger'} text-white`;
                marginBadge.textContent = `Margen: ${fin.today_margin_percent}%`;
            }

            const metaFin = document.getElementById('kpi_today_meta_fin');
            if (metaFin) {
                metaFin.textContent = `Meta Fin: $${fin.daily_target_formatted} (${fin.target_profit_percent}%)`;
            }

            // 2. Meta de Producción de Planta
            const realPkgsEl = document.getElementById('kpi_factory_real_pkgs');
            if (realPkgsEl) realPkgsEl.textContent = Number(fin.factory_real_packages).toLocaleString('es-ES', {maximumFractionDigits: 0});

            const targetPkgsEl = document.getElementById('kpi_factory_target_pkgs');
            if (targetPkgsEl) targetPkgsEl.textContent = Number(fin.factory_target_packages).toLocaleString('es-ES', {maximumFractionDigits: 0});

            const pkgKgEl = document.getElementById('kpi_today_packages_kg');
            if (pkgKgEl) {
                pkgKgEl.textContent = `${Number(fin.factory_real_packages).toLocaleString('es-ES', {maximumFractionDigits: 0})} unids/rollos (${fin.factory_real_weight_formatted} Kg)`;
            }

            const progBar = document.getElementById('kpi_factory_progress_bar');
            if (progBar) {
                progBar.style.width = Math.min(100, fin.factory_target_progress_percent) + '%';
                progBar.className = `progress-bar ${fin.is_factory_target_met ? 'bg-success' : (fin.factory_target_progress_percent >= 60 ? 'bg-warning' : 'bg-danger')}`;
            }

            const subtextEl = document.getElementById('kpi_factory_subtext');
            if (subtextEl) {
                if (fin.factory_target_packages === 0) {
                    subtextEl.innerHTML = `<small class="text-white-50 d-block mt-1" style="font-size: 11px;">Sin metas asignadas en el período</small>`;
                } else if (fin.is_factory_target_met) {
                    subtextEl.innerHTML = `<small class="text-success fw-bold d-block mt-1" style="font-size: 11px;"><i class="bi bi-check-circle-fill me-1"></i> 100% - META DE FÁBRICA CUMPLIDA (${fin.met_operators_count}/${fin.active_operators_count})</small>`;
                } else {
                    subtextEl.innerHTML = `<small class="text-white-50 d-block mt-1" style="font-size: 11px;"><span class="text-warning fw-bold">${fin.factory_target_progress_percent}% alcanzado</span> &bull; ${fin.met_operators_count}/${fin.active_operators_count} operarios al 100%</small>`;
                }
            }

            const plantStatusBadge = document.getElementById('kpi_plant_status_badge');
            if (plantStatusBadge) {
                plantStatusBadge.textContent = fin.plant_status_label;
                plantStatusBadge.className = `badge ${fin.plant_status === 'open' ? 'bg-success' : (fin.plant_status === 'closed' ? 'bg-secondary' : 'bg-dark')} text-white`;
            }

            const plantTimeInfo = document.getElementById('kpi_plant_time_info');
            if (plantTimeInfo) {
                plantTimeInfo.innerHTML = `<i class="bi bi-clock-history text-info me-1"></i>${fin.plant_schedule_window} &bull; 👥 ${fin.plant_man_hours_human}`;
            }

            // 3. Tabla de Trabajadores
            const jornadasBadge = document.getElementById('table_jornadas_badge');
            if (jornadasBadge) {
                jornadasBadge.innerHTML = `⚡ ${data.shifts.length} Jornadas &bull; ${fin.met_operators_count}/${fin.active_operators_count} Operarios al 100%`;
            }

            const tableWrapper = document.getElementById('shifts_table_wrapper');
            const emptyNotice = document.getElementById('empty_shifts_notice');
            const tbody = document.getElementById('active_shifts_tbody');

            if (!data.shifts || data.shifts.length === 0) {
                if (tableWrapper) tableWrapper.classList.add('d-none');
                if (emptyNotice) emptyNotice.classList.remove('d-none');
            } else {
                if (tableWrapper) tableWrapper.classList.remove('d-none');
                if (emptyNotice) emptyNotice.classList.add('d-none');

                if (tbody) {
                    let rowsHtml = '';
                    data.shifts.forEach(s => {
                        const isMet = s.is_met;
                        const pct = s.completion_percent;
                        const progressClass = isMet ? 'bg-success' : (pct >= 60 ? 'bg-warning' : 'bg-danger');
                        const textClass = isMet ? 'text-success' : (pct >= 60 ? 'text-warning' : 'text-danger');
                        const statusBadge = s.status === 'open'
                            ? `<span class="badge bg-success text-white" style="font-size: 10px;">🟢 Abierto</span>`
                            : `<span class="badge bg-dark text-white-50 border border-secondary" style="font-size: 10px;">⚪ Cerrado</span>`;
                        
                        const evalBadge = isMet
                            ? `<span class="badge bg-success fw-bold px-3 py-1 shadow-sm"><i class="bi bi-check-circle me-1"></i> META ALCANZADA</span>`
                            : (s.status === 'open'
                                ? `<span class="badge bg-warning text-dark fw-bold px-3 py-1 shadow-sm"><i class="bi bi-hourglass-split me-1"></i> EN PROCESO (Faltan ${s.remaining_units})</span>`
                                : `<span class="badge bg-danger fw-bold px-3 py-1 shadow-sm"><i class="bi bi-x-circle me-1"></i> NO ALCANZADA</span>`);

                        const machineBadge = s.machine_name 
                            ? `<br><small class="badge bg-dark text-info border border-secondary mt-1">${s.machine_name} (${s.machine_code || ''})</small>`
                            : '';

                        rowsHtml += `
                            <tr>
                                <td class="fw-bold text-white">
                                    <i class="bi bi-person-circle text-info me-1"></i> ${s.user_name}
                                    ${machineBadge}
                                </td>
                                <td>
                                    <span class="badge ${s.shift_type === 'diurno' ? 'bg-primary' : 'bg-secondary'}">
                                        ${s.shift_type === 'diurno' ? '☀️ Diurno' : '🌙 Nocturno'}
                                    </span>
                                    ${statusBadge}
                                </td>
                                <td>
                                    <span class="text-white">${s.start_time_human} - ${s.end_time_human}</span>
                                    <br><small class="text-info fw-bold"><i class="bi bi-stopwatch me-1"></i>${s.duration_human}</small>
                                </td>
                                <td>
                                    <strong class="text-warning">${Number(s.target_packages).toLocaleString('es-ES', {maximumFractionDigits: 0})} unids.</strong>
                                </td>
                                <td>
                                    <strong class="text-info fs-6">${Number(s.total_packages).toLocaleString('es-ES', {maximumFractionDigits: 0})} unids.</strong>
                                    <br><small class="text-white-50">(${Number(s.total_weight).toFixed(2)} Kg)</small>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="fw-bold ${textClass}">
                                            ${pct}% de su meta
                                        </small>
                                    </div>
                                    <div class="progress" style="height: 10px; background-color: #0f172a;">
                                        <div class="progress-bar ${progressClass}" role="progressbar" style="width: ${Math.min(100, pct)}%;"></div>
                                    </div>
                                </td>
                                <td>${evalBadge}</td>
                                <td>
                                    <span class="fw-bold text-${s.net_profit >= 0 ? 'success' : 'danger'} fs-6 font-monospace">
                                        $${Number(s.net_profit).toFixed(2)}
                                    </span>
                                    <br><small class="text-white-50">Margen: ${s.profit_margin_percent}%</small>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = rowsHtml;
                }
            }
        }

        function showLiveSyncToast(message) {
            const toast = document.getElementById('liveSyncToast');
            const toastText = document.getElementById('liveSyncToastText');
            if (toast && toastText) {
                toastText.textContent = message;
                toast.classList.remove('d-none');
                setTimeout(() => {
                    toast.classList.add('d-none');
                }, 4500);
            }
        }

        // Iniciar polling reactivo cada 3.5s
        setInterval(fetchDashboardUpdates, 3500);

        // Disparar consulta inmediata al retomar visibilidad de pestaña
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                fetchDashboardUpdates();
            }
        });
    });
</script>
@endsection
