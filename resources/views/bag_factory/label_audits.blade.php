@extends('layouts.app')
@section('title', 'Auditoría Forense de Etiquetas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="fs-4">🛡️</span>
            <h3 class="fw-bold mb-0 text-white">Auditoría Forense y Control de Impresión de Etiquetas</h3>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 font-monospace" style="font-size: 11px;">ANTI-FRAUDE</span>
        </div>
        <p class="text-white-50 mb-0">Monitoreo inalterable de etiquetas físicas vs producción aprobada en planta para prevenir fuga de material y duplicaciones.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('labels.index') }}" class="btn btn-outline-info fw-bold shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-tag-fill"></i> Generador de Etiquetas
        </a>
    </div>
</div>

<!-- Tarjetas de KPIs Forenses -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom mb-0 p-3 h-100 position-relative overflow-hidden" style="border-left: 4px solid #38bdf8 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Bultos Aprobados</span>
                    <h3 class="fw-bold text-white mb-0 mt-1 font-monospace">{{ number_format($kpis['total_approved_pkgs']) }}</h3>
                    <small class="text-info" style="font-size: 11px;"><i class="bi bi-check-circle me-1"></i>En Báscula & Piso</small>
                </div>
                <div class="p-3 rounded-3" style="background: rgba(56, 189, 248, 0.1);">
                    <i class="bi bi-boxes fs-2 text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom mb-0 p-3 h-100 position-relative overflow-hidden" style="border-left: 4px solid #818cf8 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Etiquetas Esperadas (Máx)</span>
                    <h3 class="fw-bold text-white mb-0 mt-1 font-monospace">{{ number_format($kpis['total_expected_labels']) }}</h3>
                    <small class="text-primary-emphasis" style="font-size: 11px;"><i class="bi bi-calculator me-1"></i>Bultos + Ficha Técnica</small>
                </div>
                <div class="p-3 rounded-3" style="background: rgba(129, 140, 248, 0.1);">
                    <i class="bi bi-ticket-detailed-fill fs-2 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom mb-0 p-3 h-100 position-relative overflow-hidden" style="border-left: 4px solid #22c55e !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Impresiones Registradas</span>
                    <h3 class="fw-bold text-white mb-0 mt-1 font-monospace">{{ number_format($kpis['total_actual_prints']) }}</h3>
                    <small class="text-success" style="font-size: 11px;"><i class="bi bi-printer-fill me-1"></i>Trazabilidad QR Activa</small>
                </div>
                <div class="p-3 rounded-3" style="background: rgba(34, 197, 94, 0.1);">
                    <i class="bi bi-printer fs-2 text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom mb-0 p-3 h-100 position-relative overflow-hidden {{ $kpis['total_anomalies'] > 0 ? 'border border-danger shadow-lg' : '' }}" style="border-left: 4px solid {{ $kpis['total_anomalies'] > 0 ? '#ef4444' : '#64748b' }} !important; {{ $kpis['total_anomalies'] > 0 ? 'background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(28, 37, 65, 1) 100%);' : '' }}">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 small text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">Alertas de Anomalía</span>
                    <h3 class="fw-bold {{ $kpis['total_anomalies'] > 0 ? 'text-danger' : 'text-success' }} mb-0 mt-1 font-monospace">
                        {{ number_format($kpis['total_anomalies']) }}
                    </h3>
                    <small class="{{ $kpis['total_anomalies'] > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 11px;">
                        @if($kpis['total_anomalies'] > 0)
                            <i class="bi bi-exclamation-octagon-fill me-1"></i>Exceso detectado
                        @else
                            <i class="bi bi-shield-fill-check me-1"></i>100% Consistente
                        @endif
                    </small>
                </div>
                <div class="p-3 rounded-3" style="background: {{ $kpis['total_anomalies'] > 0 ? 'rgba(239, 68, 68, 0.15)' : 'rgba(100, 116, 139, 0.1)' }};">
                    <i class="bi bi-shield-shaded fs-2 {{ $kpis['total_anomalies'] > 0 ? 'text-danger' : 'text-secondary' }}"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de Búsqueda -->
<div class="card-custom mb-4 p-3">
    <form method="GET" action="{{ route('bag-factory.label-audits') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label text-white-50 small fw-bold mb-1"><i class="bi bi-calendar-event me-1"></i>Fecha Desde</label>
            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label text-white-50 small fw-bold mb-1"><i class="bi bi-calendar-check me-1"></i>Fecha Hasta</label>
            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label text-white-50 small fw-bold mb-1"><i class="bi bi-person-badge me-1"></i>Operario</label>
            <select name="user_id" class="form-select">
                <option value="">Todos los Operarios</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
                <i class="bi bi-funnel-fill me-1"></i> Filtrar
            </button>
            <a href="{{ route('bag-factory.label-audits') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabla de Producciones y Auditoría -->
<div class="card-custom p-0 overflow-hidden">
    <div class="p-3 border-bottom border-secondary-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold text-white mb-0">
            <i class="bi bi-file-earmark-medical text-info me-2"></i> Registro de Auditoría y Trazabilidad de Producción
        </h5>
        <span class="badge bg-secondary font-monospace">{{ $productions->total() }} registros</span>
    </div>

    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Lote / Turno</th>
                    <th>Producto / Medida</th>
                    <th>Operario / Supervisor</th>
                    <th class="text-center">Aprobado</th>
                    <th class="text-center">Millar/Bulto</th>
                    <th class="text-center">Máx. Teórico</th>
                    <th class="text-center">Impresiones</th>
                    <th class="text-center">Estado Auditoría</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productions as $p)
                    <tr style="{{ $p->has_anomaly ? 'background-color: rgba(239, 68, 68, 0.08); border-left: 3px solid #ef4444;' : '' }}">
                        <td>
                            <span class="badge bg-dark border border-secondary text-white font-monospace px-2 py-1">LOTE-{{ $p->bag_shift_id ?? '01' }}</span>
                            <small class="text-white-50 d-block mt-1 font-monospace" style="font-size: 11px;">
                                <i class="bi bi-clock me-1"></i>{{ $p->recorded_at ? $p->recorded_at->format('d/m/Y H:i') : 'S/F' }}
                            </small>
                        </td>
                        <td>
                            <strong class="text-white d-block">{{ $p->product?->name ?? 'Producto Eliminado' }}</strong>
                            <span class="badge bg-dark text-info border border-secondary font-monospace" style="font-size: 10px;">{{ $p->product?->sku ?? 'S/SKU' }}</span>
                        </td>
                        <td>
                            <div class="text-info small fw-bold"><i class="bi bi-person-fill me-1"></i>{{ $p->shift?->user?->name ?? $p->user?->name ?? 'Operario' }}</div>
                            <small class="text-white-50" style="font-size: 11px;"><i class="bi bi-shield-check me-1"></i>{{ $p->reviewer?->name ?? 'Supervisor' }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-dark border border-secondary text-white px-2 py-1 font-monospace">
                                {{ (int)$p->quantity }} {{ $p->product?->is_variable_quantity ? 'BOB' : 'BULTOS' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="text-white-50 font-monospace">
                                {{ $p->product?->is_variable_quantity ? '1.0' : number_format((float)($p->product?->millar_per_bulto ?? 1), 0) }} M
                            </span>
                        </td>
                        <td class="text-center">
                            <strong class="text-primary font-monospace fs-6">{{ $p->max_expected_kit }}</strong>
                            <small class="text-white-50 d-block" style="font-size: 10px;">etiquetas</small>
                        </td>
                        <td class="text-center">
                            <strong class="font-monospace fs-6 {{ $p->has_anomaly ? 'text-danger' : ($p->actual_prints > 0 ? 'text-success' : 'text-white-50') }}">
                                {{ $p->actual_prints }}
                            </strong>
                            <small class="text-white-50 d-block" style="font-size: 10px;">físicas</small>
                        </td>
                        <td class="text-center">
                            @if($p->has_anomaly)
                                <span class="badge bg-danger text-white px-2 py-1 shadow-sm font-monospace">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> ANOMALÍA (+{{ $p->excess_count }})
                                </span>
                            @elseif($p->actual_prints > 0)
                                <span class="badge bg-success text-white px-2 py-1 font-monospace">
                                    <i class="bi bi-check-circle-fill me-1"></i> Consistente
                                </span>
                            @else
                                <span class="badge bg-secondary text-white-50 px-2 py-1 font-monospace">
                                    Sin Impresiones
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-outline-info btn-sm fw-bold shadow-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalAuditDetail-{{ $p->id }}">
                                <i class="bi bi-search me-1"></i> Desglose
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-white-50 py-5">
                            <i class="bi bi-shield-check fs-1 text-secondary d-block mb-2"></i>
                            <h6>No se encontraron registros de producción para auditar con los filtros seleccionados.</h6>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($productions->hasPages())
        <div class="p-3 border-top border-secondary-subtle">
            {{ $productions->links() }}
        </div>
    @endif
</div>

<!-- Modales de Desglose Forense (Fuera de la tabla) -->
@foreach($productions as $p)
    <div class="modal fade" id="modalAuditDetail-{{ $p->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content text-white bg-dark border border-secondary-subtle">
                <div class="modal-header border-secondary-subtle">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-info"></i> Auditoría Forense: LOTE-{{ $p->bag_shift_id }} / {{ $p->product?->name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 card-custom mb-0 text-center" style="background: rgba(15, 23, 42, 0.6);">
                                <small class="text-white-50 d-block text-uppercase fw-bold" style="font-size: 10px;">Bultos Aprobados</small>
                                <strong class="fs-4 text-white font-monospace">{{ (int)$p->quantity }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 card-custom mb-0 text-center" style="background: rgba(15, 23, 42, 0.6);">
                                <small class="text-white-50 d-block text-uppercase fw-bold" style="font-size: 10px;">Máximo Teórico Autorizado</small>
                                <strong class="fs-4 text-primary font-monospace">{{ $p->max_expected_kit }} <span class="fs-6 fw-normal">Etiquetas</span></strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 card-custom mb-0 text-center" style="background: rgba(15, 23, 42, 0.6);">
                                <small class="text-white-50 d-block text-uppercase fw-bold" style="font-size: 10px;">Impresiones Registradas</small>
                                <strong class="fs-4 {{ $p->has_anomaly ? 'text-danger' : 'text-success' }} font-monospace">{{ $p->actual_prints }}</strong>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary text-uppercase small mb-3">
                        <i class="bi bi-qr-code me-1 text-info"></i> Historial de Códigos QR Impresos y Trazabilidad
                    </h6>
                    <div class="table-responsive rounded-3 border border-secondary-subtle" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-custom table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Código QR</th>
                                    <th>Jerarquía</th>
                                    <th class="text-center">Veces Impreso</th>
                                    <th>Usuario / IP</th>
                                    <th>Última Impresión</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($p->labelPrints as $lp)
                                    <tr style="{{ $lp->print_count > 1 ? 'background-color: rgba(239, 68, 68, 0.1);' : '' }}">
                                        <td>
                                            <span class="badge bg-dark border border-info text-info font-monospace px-2 py-1">
                                                {{ $lp->qr_code }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary font-monospace">{{ strtoupper($lp->label_type) }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($lp->print_count > 1)
                                                <span class="badge bg-danger text-white px-2 py-1 fw-bold font-monospace">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $lp->print_count }} veces ⚠
                                                </span>
                                            @else
                                                <span class="badge bg-success text-white font-monospace">1 vez</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="d-block text-white fw-bold">{{ $lp->user?->name ?? 'Sistema' }}</small>
                                            <small class="text-white-50 font-monospace" style="font-size: 10px;">{{ $lp->ip_address ?: '127.0.0.1' }}</small>
                                        </td>
                                        <td>
                                            <small class="text-white-50 font-monospace" style="font-size: 11px;">
                                                {{ $lp->updated_at ? $lp->updated_at->format('d/m/Y H:i:s') : 'S/F' }}
                                            </small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-white-50 py-3">No hay registros de impresión para esta producción.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-secondary-subtle">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
