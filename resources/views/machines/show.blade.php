@extends('layouts.app')
@section('title', 'Perfil de Máquina - ' . $machine->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="{{ route('machines.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h3 class="fw-bold mb-0 text-white">?? {{ $machine->name }}</h3>
            <span class="badge bg-secondary fs-6">{{ $machine->code }}</span>
            <span class="badge bg-info text-dark fs-6">{{ strtoupper($machine->type) }}</span>
        </div>
        <p class="text-white-50 mb-0">Centro de Control, Historial de Lotes y Diagnóstico de Calidad</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-warning fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#createIncidentModal">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> Reportar Novedad de Calidad
        </button>
        <a href="{{ route('machines.index') }}" class="btn btn-outline-secondary">
            Volver a Máquinas
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card-custom p-3 h-100 border-start border-4 border-info">
            <div class="text-white-50 small fw-bold">TOTAL KILOS PROCESADOS</div>
            <div class="fs-3 fw-bold text-white mt-1">{{ number_format($totalKg, 2) }} <span class="fs-6 text-white-50">Kg</span></div>
            <div class="small text-info mt-1"><i class="bi bi-speedometer2 me-1"></i> Producción acumulada</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card-custom p-3 h-100 border-start border-4 border-success">
            <div class="text-white-50 small fw-bold">TOTAL UNIDADES / BULTOS</div>
            <div class="fs-3 fw-bold text-white mt-1">{{ number_format($totalUnits, 0) }} <span class="fs-6 text-white-50">unids</span></div>
            <div class="small text-success mt-1"><i class="bi bi-box-seam me-1"></i> Aprobados para stock</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card-custom p-3 h-100 border-start border-4 border-primary">
            <div class="text-white-50 small fw-bold">LOTES Y TURNOS</div>
            <div class="fs-3 fw-bold text-white mt-1">{{ $totalBatches }} <span class="fs-6 text-white-50">lotes</span></div>
            <div class="small text-primary mt-1"><i class="bi bi-clock-history me-1"></i> En {{ $totalShifts }} turnos de trabajo</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card-custom p-3 h-100 border-start border-4 {{ $openIncidents > 0 ? 'border-danger' : 'border-secondary' }}">
            <div class="text-white-50 small fw-bold">NOVEDADES / CALIDAD</div>
            <div class="fs-3 fw-bold {{ $openIncidents > 0 ? 'text-danger' : 'text-white' }} mt-1">
                {{ $openIncidents }} <span class="fs-6 text-white-50">abiertas / {{ $totalIncidents }} total</span>
            </div>
            <div class="small {{ $openIncidents > 0 ? 'text-danger' : 'text-success' }} mt-1">
                <i class="bi bi-shield-check me-1"></i> {{ $openIncidents > 0 ? 'Requiere atención' : 'Todo operativo' }}
            </div>
        </div>
    </div>
</div>

<!-- Desglose de Productos Fabricados en esta Máquina -->
<div class="card-custom mb-4">
    <div class="p-3 border-bottom border-secondary-subtle d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-white mb-0">?? Productos y Medidas Fabricadas en {{ $machine->name }}</h5>
        <span class="badge bg-secondary">{{ $productsBreakdown->count() }} tipos de bolsas</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Producto / Medida</th>
                    <th>SKU</th>
                    <th>Lotes Pesados</th>
                    <th>Total Cantidad</th>
                    <th>Kilos Producidos</th>
                    <th>% Kilos Máquina</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productsBreakdown as $pb)
                    @php
                        $pct = $totalKg > 0 ? round(($pb->total_weight / $totalKg) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td class="fw-bold text-white">{{ $pb->product?->name ?? 'Producto #' . $pb->product_id }}</td>
                        <td><span class="badge bg-secondary">{{ $pb->product?->sku ?? 'S/SKU' }}</span></td>
                        <td>{{ $pb->batches_count }} lotes</td>
                        <td class="fw-bold">{{ number_format($pb->total_qty, 0) }} unids/bultos</td>
                        <td class="fw-bold text-info">{{ number_format($pb->total_weight, 2) }} Kg</td>
                        <td>
                            <div class="d-flex align-items-center gap-2" style="min-width: 120px;">
                                <div class="progress flex-grow-1" style="height: 6px; background-color: rgba(255,255,255,0.1);">
                                    <div class="progress-bar bg-info" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="small text-white-50">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-white-50">
                            No se han registrado producciones aprobadas para esta máquina todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pestañas: Historial de Producción y Control de Novedades -->
<div class="card-custom">
    <div class="p-3 border-bottom border-secondary-subtle">
        <ul class="nav nav-pills gap-2" id="machineTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane">
                    <i class="bi bi-clock-history me-1"></i> Historial de Lotes Producidos ({{ $productionsHistory->total() }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold" id="incidents-tab" data-bs-toggle="tab" data-bs-target="#incidents-pane">
                    <i class="bi bi-shield-exclamation me-1"></i> Novedades y Control de Calidad ({{ $incidents->count() }})
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content p-3">
        <!-- Tab 1: Historial de Producción -->
        <div class="tab-pane fade show active" id="history-pane">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Lote</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Peso Real</th>
                            <th>Operario</th>
                            <th>Estado</th>
                            <th class="text-end">Ticket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productionsHistory as $p)
                            <tr>
                                <td class="small text-white-50">
                                    {{ $p->recorded_at ? $p->recorded_at->format('d/m/Y H:i') : ($p->created_at ? $p->created_at->format('d/m/Y H:i') : '-') }}
                                </td>
                                <td>
                                    <span class="badge bg-secondary font-monospace">
                                        L{{ $p->recorded_at ? $p->recorded_at->format('ymd') : date('ymd') }}-{{ $machine->code }}
                                    </span>
                                </td>
                                <td class="fw-bold text-white">{{ $p->product?->name ?? 'N/A' }}</td>
                                <td>{{ number_format($p->quantity, 0) }} unids</td>
                                <td class="fw-bold text-info">{{ number_format($p->weight, 2) }} Kg</td>
                                <td>{{ $p->shift?->user?->name ?? $p->user?->name ?? 'Operario' }}</td>
                                <td>
                                    @if($p->status === 'approved')
                                        <span class="badge bg-success">Aprobado</span>
                                    @elseif($p->status === 'rejected')
                                        <span class="badge bg-danger">Rechazado</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('ticket', $p->id) }}" target="_blank" class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-qr-code"></i> Ticket
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-white-50">
                                    No hay registros de producción para esta máquina.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $productionsHistory->links() }}
            </div>
        </div>

        <!-- Tab 2: Novedades de Calidad y Causa Raíz -->
        <div class="tab-pane fade" id="incidents-pane">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-white-50 mb-0 small">
                    Diagnóstico cruzado para evaluar si las fallas son de origen <strong>Mecánico (Máquina)</strong> o de <strong>Formulación (Materia Prima)</strong>.
                </p>
                <button class="btn btn-warning btn-sm fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#createIncidentModal">
                    <i class="bi bi-plus-circle-fill me-1"></i> Nueva Novedad
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Diagnóstico / Causa Raíz</th>
                            <th>Título & Descripción</th>
                            <th>Producto / Lote</th>
                            <th>Severidad</th>
                            <th>Estado</th>
                            <th>Reportado Por</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incidents as $inc)
                            <tr>
                                <td class="small text-white-50">{{ $inc->created_at->format('d/m/Y') }}</td>
                                <td>
                                    @if($inc->root_cause_analysis === 'maquina')
                                        <span class="badge bg-danger border border-danger-subtle">
                                            ?? FALLA DE MÁQUINA
                                        </span>
                                    @elseif($inc->root_cause_analysis === 'formula')
                                        <span class="badge bg-primary border border-primary-subtle">
                                            ?? FALLA DE FÓRMULA / MEZCLA
                                        </span>
                                    @elseif($inc->root_cause_analysis === 'operador')
                                        <span class="badge bg-warning text-dark border border-warning-subtle">
                                            ?? AJUSTE OPERATIVO
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">OTRA CAUSA</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-white">{{ $inc->title }}</div>
                                    <div class="small text-white-50" style="max-width: 280px;">{{ $inc->description }}</div>
                                    @if($inc->resolution_notes)
                                        <div class="small text-success mt-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> Solución: {{ $inc->resolution_notes }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($inc->product)
                                        <div class="small fw-bold text-white">{{ $inc->product->name }}</div>
                                    @endif
                                    @if($inc->batch_code)
                                        <span class="badge bg-dark border border-secondary font-monospace">{{ $inc->batch_code }}</span>
                                    @else
                                        <span class="text-white-50 small">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($inc->severity === 'critica')
                                        <span class="badge bg-danger">CRÍTICA</span>
                                    @elseif($inc->severity === 'alta')
                                        <span class="badge bg-danger-subtle text-danger">ALTA</span>
                                    @elseif($inc->severity === 'media')
                                        <span class="badge bg-warning-subtle text-warning">MEDIA</span>
                                    @else
                                        <span class="badge bg-secondary">BAJA</span>
                                    @endif
                                </td>
                                <td>
                                    @if($inc->status === 'resuelta')
                                        <span class="badge bg-success">Resuelta</span>
                                    @elseif($inc->status === 'en_revision')
                                        <span class="badge bg-info text-dark">En Revisión</span>
                                    @else
                                        <span class="badge bg-danger">Abierta</span>
                                    @endif
                                </td>
                                <td class="small">{{ $inc->user?->name ?? 'Auditor' }}</td>
                                <td class="text-end">
                                    @if($inc->status !== 'resuelta')
                                        <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#resolveIncidentModal{{ $inc->id }}">
                                            <i class="bi bi-check2-circle me-1"></i> Resolver
                                        </button>

                                        <!-- Modal Resolver -->
                                        <div class="modal fade" id="resolveIncidentModal{{ $inc->id }}" tabindex="-1">
                                            <div class="modal-dialog text-start">
                                                <div class="modal-content">
                                                    <form action="{{ route('machines.incidents.resolve', ['id' => $machine->id, 'incidentId' => $inc->id]) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header border-secondary-subtle">
                                                            <h5 class="modal-title fw-bold">Cerrar Novedad de Calidad</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-white-50 mb-2">
                                                                Novedad: <strong>{{ $inc->title }}</strong>
                                                            </p>
                                                            <div class="mb-3">
                                                                <label class="form-label small text-white-50">Acciones Tomadas / Solución</label>
                                                                <textarea name="resolution_notes" class="form-control" rows="3" placeholder="Ej. Se ajustó temperatura de boquilla y se calibró la cuchilla de corte..." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-secondary-subtle">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-success btn-sm fw-bold">Marcar como Resuelta</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="bi bi-check-all"></i> Resuelta
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-white-50">
                                    No hay novedades ni incidencias reportadas para esta máquina.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reportar Novedad -->
<div class="modal fade" id="createIncidentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('machines.incidents.store', $machine->id) }}" method="POST">
                @csrf
                <div class="modal-header border-secondary-subtle">
                    <h5 class="modal-title fw-bold">Reportar Novedad en {{ $machine->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Título de la Novedad / Reclamo</label>
                        <input type="text" name="title" class="form-control" placeholder="Ej. Variación de espesor en burbuja / Sellado débil" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-white-50">Origen de la Falla</label>
                            <select name="incident_type" class="form-select" required>
                                <option value="mecanica">?? Falla Mecánica (Máquina)</option>
                                <option value="formula">?? Falla de Mezcla / Resina (Fórmula)</option>
                                <option value="operacion">?? Ajuste Operativo (Tensión/Corte)</option>
                                <option value="otra">Otra Causa</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-white-50">Severidad</label>
                            <select name="severity" class="form-select" required>
                                <option value="baja">Baja</option>
                                <option value="media" selected>Media</option>
                                <option value="alta">Alta</option>
                                <option value="critica">Crítica (Parada de Máquina)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-white-50">Producto Afectado (Opcional)</label>
                            <select name="product_id" class="form-select">
                                <option value="">-- Seleccionar producto --</option>
                                @foreach($allProducts as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-white-50">Código de Lote (Opcional)</label>
                            <input type="text" name="batch_code" class="form-control" placeholder="Ej. L260909-EXT01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Descripción Detallada</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Explica detalladamente la novedad observada (grumos, rotura, calibre disparejo, sellado que se abre...)" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary-subtle">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark">Guardar Novedad</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
