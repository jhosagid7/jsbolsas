@extends('layouts.app')
@section('title', 'Auditoría en Báscula y Control de Calidad')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">⚖️ Auditoría en Báscula y Pre-Levantamiento</h3>
        <p class="text-white-50 mb-0">Inspección de bultos y bobinas individuales en tiempo real, pesaje exacto, desglose por rollo y aprobación.</p>
    </div>
    @if($pendingProductions->isNotEmpty())
        <form action="{{ route('bulk_approve') }}" method="POST" onsubmit="return confirm('¿Aprobar todos los registros pendientes para Pre-Levantamiento?');">
            @csrf
            @foreach($pendingProductions as $p)
                <input type="hidden" name="ids[]" value="{{ $p->id }}">
            @endforeach
            <button type="submit" class="btn btn-success fw-bold shadow-sm">
                <i class="bi bi-check-all me-1"></i> Aprobar Todo el Lote ({{ $pendingProductions->count() }})
            </button>
        </form>
    @endif
</div>

@if(isset($clinicalReport) && $clinicalReport)
<div class="card-custom mb-4 border border-info">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-info mb-0">
            <i class="bi bi-qr-code-scan me-2"></i> Reporte Clínico de Trazabilidad & Pesaje
        </h5>
        <span class="badge bg-info text-dark font-monospace fs-6">{{ $clinicalReport->qr_code }}</span>
    </div>
    <div class="row g-3">
        <div class="col-md-3">
            <small class="text-white-50 d-block">Producto:</small>
            <span class="fw-bold text-white">{{ $clinicalReport->product->name ?? 'N/A' }}</span>
        </div>
        <div class="col-md-3">
            <small class="text-white-50 d-block">Operario:</small>
            <span class="fw-bold text-white">{{ $clinicalReport->user->name ?? 'N/A' }}</span>
        </div>
        <div class="col-md-3">
            <small class="text-white-50 d-block">Máquina / Línea:</small>
            <span class="fw-bold text-warning">
                {{ $clinicalReport->machine->code ?? 'N/A' }} - {{ $clinicalReport->machine->name ?? 'N/A' }}
            </span>
        </div>
        <div class="col-md-3">
            <small class="text-white-50 d-block">Peso Total:</small>
            <span class="fw-bold text-success font-monospace fs-5">{{ number_format($clinicalReport->weight, 2) }} Kg</span>
        </div>
    </div>
</div>
@endif

<!-- Pendientes de Auditoría -->
<div class="card-custom mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-warning mb-0">
            <i class="bi bi-clock-history me-2"></i> Registros Pendientes de Revisión en Báscula ({{ $pendingProductions->count() }})
        </h5>
    </div>

    @if($pendingProductions->isEmpty())
        <div class="p-5 text-center text-white-50">
            <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
            <h5>¡Todo al día! No hay registros pendientes de pesaje en báscula.</h5>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Operario</th>
                        <th>Producto / Medida</th>
                        <th>Cant. / Presentación</th>
                        <th>
                            Peso Total Báscula
                            <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none ms-1" 
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="hover focus" 
                                    data-bs-placement="top" 
                                    data-bs-html="true" 
                                    data-bs-custom-class="dark-info-popover shadow-lg" 
                                    title="ℹ️ Peso Total Báscula" 
                                    data-bs-content="Peso total físico acumulado en kilogramos registrado al momento del pesaje."
                                    tabindex="0"
                                    aria-label="Información sobre Peso Total">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>
                        </th>
                        <th style="min-width: 250px;">
                            Desglose de Bobinas / Rollos
                            <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none ms-1" 
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="hover focus" 
                                    data-bs-placement="top" 
                                    data-bs-html="true" 
                                    data-bs-custom-class="dark-info-popover shadow-lg" 
                                    title="ℹ️ Desglose Individual de Bobinas" 
                                    data-bs-content="Muestra cada bobina o rollo pesado por separado con su peso exacto. Permite auditar y editar cada bobina de forma individual antes de la aprobación final."
                                    tabindex="0"
                                    aria-label="Información sobre Desglose de Bobinas">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>
                        </th>
                        <th class="text-end" style="min-width: 170px;">
                            Acciones
                            <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none ms-1" 
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="hover focus" 
                                    data-bs-placement="top" 
                                    data-bs-html="true" 
                                    data-bs-custom-class="dark-info-popover shadow-lg" 
                                    title="ℹ️ Acciones de Auditoría" 
                                    data-bs-content="• <strong>Aprobar:</strong> Valida el pesaje y envía el lote al área de Pre-Levantamiento / Almacén.<br>• <strong>Editar:</strong> Modifica pesos individuales o datos en caso de discrepancia física.<br>• <strong>Rechazar:</strong> Devuelve el registro al operario con observaciones."
                                    tabindex="0"
                                    aria-label="Información sobre Acciones">
                                <i class="bi bi-info-circle-fill"></i>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingProductions as $prod)
                        @php
                            $isBobina = ($prod->product && $prod->product->is_variable_quantity) || 
                                        ($prod->product && in_array(strtoupper($prod->product->sale_unit ?? ''), ['BOBINA', 'KG', 'ROLLO'])) ||
                                        str_contains(strtoupper($prod->product->name ?? ''), 'BOBINA') || 
                                        str_contains(strtoupper($prod->product->name ?? ''), 'ROLLO') || 
                                        !empty($prod->metadata);
                            $hasRolls = !empty($prod->metadata) && is_array($prod->metadata) && count($prod->metadata) > 0;
                        @endphp
                        <tr>
                            <td>
                                <span class="text-white">{{ $prod->recorded_at ? $prod->recorded_at->format('d/m/Y') : '-' }}</span>
                                <br><small class="text-white-50">{{ $prod->recorded_at ? $prod->recorded_at->format('h:i A') : '' }}</small>
                            </td>
                            <td class="fw-bold text-white">
                                <i class="bi bi-person-circle me-1 text-info"></i> {{ $prod->user->name ?? 'Operario' }}
                            </td>
                            <td>
                                <span class="fw-bold text-info">{{ $prod->product->name ?? 'Producto' }}</span>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    @if($isBobina)
                                        <span class="badge bg-warning text-dark fw-bold" style="font-size: 10px;">🔄 BOBINA / X KG</span>
                                    @endif
                                    @if($prod->product && $prod->product->sku)
                                        <small class="text-white-50 font-monospace">SKU: {{ $prod->product->sku }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $isBobina ? 'bg-warning text-dark' : 'bg-primary' }} fs-6">
                                    {{ number_format($prod->quantity, 0) }} {{ $isBobina ? 'Bobinas' : ($prod->product?->sale_unit ?? 'Bultos/Paq.') }}
                                </span>
                            </td>
                            <td>
                                <span class="text-warning fw-bold fs-6">{{ number_format($prod->weight, 2) }} Kg</span>
                                @if($prod->original_weight && (float)$prod->original_weight !== (float)$prod->weight)
                                    <br><small class="text-danger"><del>Orig: {{ number_format($prod->original_weight, 2) }} Kg</del></small>
                                @endif
                            </td>
                            <td>
                                @if($hasRolls)
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($prod->metadata as $idx => $r)
                                            <div class="p-1 px-2 bg-dark rounded border border-warning-subtle d-flex justify-content-between align-items-center" style="font-size: 11px;">
                                                <span class="text-warning fw-bold"><i class="bi bi-disc me-1"></i> Bobina #{{ $idx + 1 }}:</span>
                                                <strong class="text-white font-monospace">{{ number_format($r['weight'] ?? 0, 2) }} Kg</strong>
                                                @if(!empty($r['color'])) <span class="badge bg-secondary" style="font-size: 9px;">{{ $r['color'] }}</span> @endif
                                                @if(!empty($r['batch'])) <span class="text-white-50" style="font-size: 9px;">L:{{ $r['batch'] }}</span> @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($isBobina)
                                    <div>
                                        <span class="badge bg-dark border border-warning text-warning py-1 px-2" style="font-size: 11px;">
                                            ⚠️ {{ number_format($prod->quantity, 0) }} Bobinas sin pesaje individual
                                        </span>
                                        <br>
                                        <button class="btn btn-outline-warning btn-sm py-0 px-2 mt-1 fw-bold" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $prod->id }}" style="font-size: 11px;">
                                            ⚖️ Pesar y Desglosar Bobinas
                                        </button>
                                    </div>
                                @else
                                    <span class="text-white-50 small">Carga Global Estándar</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <button class="btn btn-outline-warning btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $prod->id }}">
                                        <i class="bi bi-pencil-square me-1"></i> Editar
                                    </button>
                                    <form action="{{ route('approve', $prod->id) }}" method="POST" class="d-inline mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm fw-bold">
                                            <i class="bi bi-check-lg"></i> Aprobar
                                        </button>
                                    </form>
                                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $prod->id }}" title="Rechazar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Modales de Edición y Rechazo (Fuera de la tabla) -->
@foreach($pendingProductions as $prod)
    @php
        $isBobina = ($prod->product && $prod->product->is_variable_quantity) || 
                    ($prod->product && in_array(strtoupper($prod->product->sale_unit ?? ''), ['BOBINA', 'KG', 'ROLLO'])) ||
                    str_contains(strtoupper($prod->product->name ?? ''), 'BOBINA') || 
                    str_contains(strtoupper($prod->product->name ?? ''), 'ROLLO') || 
                    !empty($prod->metadata);
        $hasRolls = !empty($prod->metadata) && is_array($prod->metadata) && count($prod->metadata) > 0;
        $rollCount = $hasRolls ? count($prod->metadata) : (int)max(1, $prod->quantity);
    @endphp

    <!-- Modal de Edición Completa en Báscula -->
    <div class="modal fade" id="adjustModal{{ $prod->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-dark text-white border-secondary">
                <form action="{{ route('adjust', $prod->id) }}" method="POST" id="formAdjust{{ $prod->id }}">
                    @csrf
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title fw-bold text-warning">
                            <i class="bi bi-pencil-square me-2"></i> Auditoría & Desglose en Báscula #{{ $prod->id }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if($isBobina)
                            <div class="alert alert-warning py-2 small mb-3 border-warning">
                                <i class="bi bi-info-circle-fill me-1"></i> <strong>Producto Tipo Bobina / Venta por Kilo:</strong> Registra y verifica el peso individual de cada bobina pesada en báscula. Al venderse, cada bobina se despachará con su peso exacto.
                            </div>
                        @endif

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small text-white-50">Producto / Tipo de Bolsa</label>
                                <select name="product_id" class="form-select bg-secondary text-white border-0" required>
                                    @foreach($allProducts as $p)
                                        <option value="{{ $p->id }}" {{ $prod->product_id == $p->id ? 'selected' : '' }}>
                                            {{ $p->name }} (SKU: {{ $p->sku ?? 'N/A' }}) {{ $p->is_variable_quantity ? '[BOBINA]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-white-50">Operario Asignado</label>
                                <select name="user_id" class="form-select bg-secondary text-white border-0" required>
                                    @foreach($allUsers as $u)
                                        <option value="{{ $u->id }}" {{ $prod->user_id == $u->id ? 'selected' : '' }}>
                                            {{ $u->name }} ({{ strtoupper($u->role) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Sección de Desglose de Rollos / Bobinas Individuales -->
                        <div class="p-3 bg-black rounded border border-warning mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-warning mb-0">
                                    <i class="bi bi-disc me-1"></i> Desglose de Pesaje por Cada Bobina / Rollo Individual
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-warning fw-bold" onclick="addRollRow({{ $prod->id }})">
                                    <i class="bi bi-plus-circle me-1"></i> + Agregar Bobina
                                </button>
                            </div>
                            <small class="text-white-50 d-block mb-3">
                                Pesa cada bobina en la báscula e ingresa su peso individual abajo. La cantidad y el peso total se calcularán automáticamente.
                            </small>

                            <div class="table-responsive">
                                <table class="table table-sm table-dark mb-0 align-middle">
                                    <thead>
                                        <tr class="bg-dark">
                                            <th width="8%">#</th>
                                            <th width="42%">Peso de la Bobina (Kg)</th>
                                            <th width="25%">Color (Opc.)</th>
                                            <th width="20%">Lote (Opc.)</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="rollsBody{{ $prod->id }}">
                                        @if($hasRolls)
                                            @foreach($prod->metadata as $idx => $r)
                                                <tr class="roll-row-{{ $prod->id }}">
                                                    <td class="text-warning fw-bold align-middle roll-num-{{ $prod->id }}">{{ $idx + 1 }}</td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.01" name="rolls[{{ $idx }}][weight]" class="form-control form-control-sm roll-weight-{{ $prod->id }} text-warning fw-bold fs-6" value="{{ $r['weight'] ?? '' }}" placeholder="0.00" required oninput="recalcTotals({{ $prod->id }})">
                                                            <span class="input-group-text bg-secondary text-white">Kg</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="rolls[{{ $idx }}][color]" class="form-control form-control-sm text-white" value="{{ $r['color'] ?? '' }}" placeholder="Color">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="rolls[{{ $idx }}][batch]" class="form-control form-control-sm text-white" value="{{ $r['batch'] ?? '' }}" placeholder="Lote">
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="removeRollRow(this, {{ $prod->id }})">🗑️</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @elseif($isBobina && (int)$prod->quantity > 0)
                                            @php
                                                $initialQty = (int)$prod->quantity;
                                                $estimatedWeight = $initialQty > 0 ? round((float)$prod->weight / $initialQty, 2) : 0;
                                            @endphp
                                            @for($i = 0; $i < $initialQty; $i++)
                                                <tr class="roll-row-{{ $prod->id }}">
                                                    <td class="text-warning fw-bold align-middle roll-num-{{ $prod->id }}">{{ $i + 1 }}</td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.01" name="rolls[{{ $i }}][weight]" class="form-control form-control-sm roll-weight-{{ $prod->id }} text-warning fw-bold fs-6" value="{{ $estimatedWeight }}" placeholder="0.00" required oninput="recalcTotals({{ $prod->id }})">
                                                            <span class="input-group-text bg-secondary text-white">Kg</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="rolls[{{ $i }}][color]" class="form-control form-control-sm text-white" placeholder="Color">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="rolls[{{ $i }}][batch]" class="form-control form-control-sm text-white" placeholder="Lote">
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="removeRollRow(this, {{ $prod->id }})">🗑️</button>
                                                    </td>
                                                </tr>
                                            @endfor
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-white-50">Cantidad Total (Bobinas / Bultos)</label>
                                <input type="number" step="1" name="quantity" id="qtyInput{{ $prod->id }}" class="form-control form-control-lg text-center fw-bold" value="{{ $prod->quantity }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-white-50">Peso Total Acumulado (Kilogramos)</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" step="0.01" name="weight" id="weightInput{{ $prod->id }}" class="form-control text-center text-warning fw-bold fs-4" value="{{ $prod->weight }}" required>
                                    <span class="input-group-text bg-dark border-secondary text-warning fw-bold">Kg</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning btn-sm fw-bold">
                            <i class="bi bi-save me-1"></i> Guardar Desglose de Báscula
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Rechazo -->
    <div class="modal fade" id="rejectModal{{ $prod->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white border-danger">
                <form action="{{ route('reject', $prod->id) }}" method="POST">
                    @csrf
                    <div class="modal-header border-danger">
                        <h5 class="modal-title fw-bold text-danger">Rechazar Registro por Calidad</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small text-white-50">Motivo del Rechazo / Falla Técnica</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Ej. Calibre fuera de tolerancia, bobina floja, rotura, peso incorrecto..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-danger">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger btn-sm fw-bold">Confirmar Rechazo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<!-- Aprobados Recientes (Pre-Levantamiento) -->
<div class="card-custom">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-success mb-0">
            <i class="bi bi-qr-code-scan me-2"></i> Stock Aprobado en Pre-Levantamiento (Listo para Almacén General)
        </h5>
    </div>

    @if($recentApproved->isEmpty())
        <p class="text-white-50 mb-0">No hay bultos ni bobinas aprobadas recientemente.</p>
    @else
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Código QR</th>
                        <th>Producto / Medida</th>
                        <th>Operario</th>
                        <th>Cant. / Presentación</th>
                        <th>Peso Total</th>
                        <th>Desglose Individual</th>
                        <th>Auditado Por</th>
                        <th>Fecha Aprobación</th>
                        <th class="text-end">Ticket</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentApproved as $item)
                        @php
                            $isBobina = ($item->product && $item->product->is_variable_quantity) || 
                                        ($item->product && in_array(strtoupper($item->product->sale_unit ?? ''), ['BOBINA', 'KG', 'ROLLO'])) ||
                                        str_contains(strtoupper($item->product->name ?? ''), 'BOBINA') || 
                                        !empty($item->metadata);
                            $hasRolls = !empty($item->metadata) && is_array($item->metadata) && count($item->metadata) > 0;
                        @endphp
                        <tr>
                            <td><span class="badge bg-info text-dark fw-bold font-monospace">{{ $item->qr_code }}</span></td>
                            <td class="fw-bold text-white">
                                {{ $item->product->name ?? 'Bolsa' }}
                                @if($isBobina)
                                    <br><span class="badge bg-warning text-dark" style="font-size: 10px;">🔄 BOBINA</span>
                                @endif
                            </td>
                            <td>{{ $item->user->name ?? 'Operario' }}</td>
                            <td>
                                <span class="badge {{ $isBobina ? 'bg-warning text-dark' : 'bg-primary' }}">
                                    {{ number_format($item->quantity, 0) }} {{ $isBobina ? 'Bobinas' : ($item->product?->sale_unit ?? 'Bultos') }}
                                </span>
                            </td>
                            <td class="text-success fw-bold font-monospace fs-6">{{ number_format($item->weight, 2) }} Kg</td>
                            <td>
                                @if($hasRolls)
                                    <div class="d-flex flex-column gap-1" style="font-size: 11px;">
                                        @foreach($item->metadata as $idx => $r)
                                            <span class="badge bg-dark border border-success text-success text-start py-1">
                                                Bobina #{{ $idx + 1 }}: <strong class="text-white">{{ number_format($r['weight'] ?? 0, 2) }} Kg</strong>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-white-50 small">Carga Estándar</span>
                                @endif
                            </td>
                            <td>{{ $item->reviewer->name ?? 'Supervisor' }}</td>
                            <td>{{ $item->reviewed_at ? $item->reviewed_at->format('d/m/Y h:i A') : '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('ticket', $item->id) }}" target="_blank" class="btn btn-outline-light btn-sm fw-bold">
                                    🖨️ Ticket QR
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
function addRollRow(prodId) {
    const tbody = document.getElementById('rollsBody' + prodId);
    const index = tbody.getElementsByTagName('tr').length;
    const tr = document.createElement('tr');
    tr.className = 'roll-row-' + prodId;
    tr.innerHTML = `
        <td class="text-warning fw-bold align-middle roll-num-${prodId}">${index + 1}</td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" step="0.01" name="rolls[${index}][weight]" class="form-control form-control-sm roll-weight-${prodId} text-warning fw-bold fs-6" placeholder="0.00" required oninput="recalcTotals(${prodId})">
                <span class="input-group-text bg-secondary text-white">Kg</span>
            </div>
        </td>
        <td>
            <input type="text" name="rolls[${index}][color]" class="form-control form-control-sm text-white" placeholder="Color">
        </td>
        <td>
            <input type="text" name="rolls[${index}][batch]" class="form-control form-control-sm text-white" placeholder="Lote">
        </td>
        <td>
            <button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="removeRollRow(this, ${prodId})">🗑️</button>
        </td>
    `;
    tbody.appendChild(tr);
    recalcTotals(prodId);
}

function removeRollRow(btn, prodId) {
    const row = btn.closest('tr');
    row.remove();
    
    // Reindexar números
    const numbers = document.querySelectorAll('.roll-num-' + prodId);
    numbers.forEach((numElem, idx) => {
        numElem.innerText = idx + 1;
    });

    recalcTotals(prodId);
}

function recalcTotals(prodId) {
    const weights = document.querySelectorAll('.roll-weight-' + prodId);
    let total = 0;
    let count = 0;
    weights.forEach(w => {
        const val = parseFloat(w.value) || 0;
        if (val > 0) {
            total += val;
            count++;
        }
    });

    if (count > 0) {
        document.getElementById('qtyInput' + prodId).value = count;
        document.getElementById('weightInput' + prodId).value = total.toFixed(2);
    }
}
</script>
@endsection
