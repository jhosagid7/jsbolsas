@extends('layouts.app')
@section('title', 'Control de Inventario - Dos Depósitos de Materia Prima')

@section('content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-white">🏭 Inventario de Materia Prima: Dos Depósitos</h3>
            <p class="text-white-50 mb-0">Control de flujo: <strong>Almacén Principal</strong> (Resguardo) ➔ <strong>Depósito de Planta</strong> (Pie de Tolva).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('raw_materials.audit') }}" class="btn btn-warning fw-bold d-flex align-items-center gap-1">
                <span>⚖️</span> Arqueo & Balance Semanal
            </a>
            <a href="{{ route('raw_materials.index') }}" class="btn btn-outline-info fw-bold">
                <i class="bi bi-boxes me-1"></i> Precios de Insumos
            </a>
        </div>
    </div>

    {{-- Alertas de Sesión --}}
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show bg-success text-white border-0 py-2 mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('status') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show bg-danger text-white border-0 py-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tarjetas Resumen de Ambos Depósitos --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card border-0 p-3 shadow-sm h-100" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-left: 5px solid #3b82f6 !important; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase fw-bold text-info small" style="letter-spacing: 0.5px;">ALMACÉN PRINCIPAL</span>
                        <h2 class="text-white fw-bold mb-0 font-monospace">
                            {{ number_format($principalStocks->sum('total_kg'), 2) }} <span class="fs-6 text-white-50">Kg</span>
                        </h2>
                        <span class="text-white-50 small">{{ number_format($principalStocks->sum('sacos_count'), 1) }} sacos en depósito central</span>
                    </div>
                    <div class="fs-1 text-info opacity-50">🏢</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card border-0 p-3 shadow-sm h-100" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-left: 5px solid #10b981 !important; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase fw-bold text-success small" style="letter-spacing: 0.5px;">DEPÓSITO DE PLANTA (PIE DE TOLVA)</span>
                        <h2 class="text-white fw-bold mb-0 font-monospace">
                            {{ number_format($plantaStocks->sum('total_kg'), 2) }} <span class="fs-6 text-white-50">Kg</span>
                        </h2>
                        <span class="text-white-50 small">{{ number_format($plantaStocks->sum('sacos_count'), 1) }} sacos disponibles para extruir</span>
                    </div>
                    <div class="fs-1 text-success opacity-50">🌀</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla Comparativa de Materiales por Depósito --}}
    <div class="card border-0 shadow-sm mb-4" style="background: #1e293b; border-radius: 14px; border: 1px solid #334155 !important;">
        <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                <span>📦</span> Existencias por Insumo en Ambos Almacenes
            </h5>
            <button class="btn btn-primary btn-sm fw-bold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#transferModal">
                <span>🚚</span> Transferir Sacos a Planta
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="text-white-50 small text-uppercase" style="background: #0f172a;">
                    <tr>
                        <th class="ps-3">Insumo / Resina</th>
                        <th class="text-center text-info">Almacén Principal (Sacos)</th>
                        <th class="text-center text-info">Almacén Principal (Kg)</th>
                        <th class="text-center text-success">Planta / Tolva (Sacos)</th>
                        <th class="text-center text-success">Planta / Tolva (Kg)</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $mat)
                        @php
                            $sp = $principalStocks->get($mat->id);
                            $sl = $plantaStocks->get($mat->id);
                            $pSacos = $sp ? (float)$sp->sacos_count : 0.0;
                            $pKg = $sp ? (float)$sp->total_kg : 0.0;
                            $lSacos = $sl ? (float)$sl->sacos_count : 0.0;
                            $lKg = $sl ? (float)$sl->total_kg : 0.0;
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <strong class="text-white">{{ $mat->name }}</strong>
                                <span class="text-white-50 small d-block">{{ $mat->code ?? 'S/C' }}</span>
                            </td>
                            <td class="text-center font-monospace fs-6 fw-bold text-info">
                                {{ number_format($pSacos, 1) }}
                            </td>
                            <td class="text-center font-monospace text-white-50">
                                {{ number_format($pKg, 2) }} Kg
                            </td>
                            @php
                                $plantaClass = $lKg <= 0 ? 'text-danger' : ($lKg <= 25.0 ? 'text-warning' : 'text-success');
                            @endphp
                            <td class="text-center font-monospace fs-6 fw-bold {{ $plantaClass }}">
                                {{ number_format($lSacos, 1) }}
                                @if($lKg <= 0)
                                    <span class="badge bg-danger text-white ms-1" style="font-size: 9px;" title="Agotado en Planta Tolva">AGOTADO</span>
                                @elseif($lKg <= 25.0)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 9px;" title="Stock bajo en Planta Tolva">⚠️ Reponer</span>
                                @endif
                            </td>
                            <td class="text-center font-monospace {{ $plantaClass }}">
                                {{ number_format($lKg, 2) }} Kg
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex justify-content-end gap-1">
                                    <button class="btn btn-outline-info btn-sm" title="Ajustar stock inicial"
                                            onclick="openInitStockModal({{ $mat->id }}, '{{ addslashes($mat->name) }}')">
                                        ⚙️ Ajustar Stock
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-white-50">
                                No hay materias primas registradas en el catálogo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Historial de Transferencias Recientes --}}
    <div class="card border-0 shadow-sm" style="background: #1e293b; border-radius: 14px; border: 1px solid #334155 !important;">
        <div class="card-header bg-transparent border-secondary py-3">
            <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                <span>📋</span> Movimientos Recientes de Transferencia a Planta
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="text-white-50 small text-uppercase" style="background: #0f172a;">
                    <tr>
                        <th class="ps-3">Fecha y Hora</th>
                        <th>Insumo</th>
                        <th class="text-center">Sacos Movilizados</th>
                        <th class="text-center">Kilos Netos</th>
                        <th>Responsable</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $tr)
                        <tr>
                            <td class="ps-3 font-monospace text-white-50">
                                {{ $tr->transferred_at ? $tr->transferred_at->format('d/m/Y h:i A') : '--' }}
                            </td>
                            <td>
                                <strong class="text-white">{{ $tr->rawMaterial->name ?? 'Insumo' }}</strong>
                            </td>
                            <td class="text-center font-monospace fw-bold text-warning">
                                {{ number_format($tr->sacos_transferred, 1) }} sacos
                            </td>
                            <td class="text-center font-monospace fw-bold text-success">
                                {{ number_format($tr->weight_kg, 2) }} Kg
                            </td>
                            <td class="text-white-50 small">
                                {{ $tr->transferredByUser->name ?? 'Usuario' }}
                            </td>
                            <td class="text-white-50 small">
                                {{ $tr->notes ?: 'Sin notas' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-white-50">
                                Aún no se han registrado transferencias hacia la planta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL PARA TRANSFERENCIA DE SACOS --}}
<div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-white" style="background: #1e293b; border: 1px solid #3b82f6; border-radius: 14px;">
            <div class="modal-header border-secondary py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <span>🚚</span> Transferir Sacos a Planta (Pie de Tolva)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('raw_materials.transfers.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Materia Prima / Resina:</label>
                        <select name="raw_material_id" class="form-select bg-dark text-white border-secondary" required>
                            @foreach($materials as $m)
                                @php $avail = $principalStocks->get($m->id)?->sacos_count ?? 0; @endphp
                                <option value="{{ $m->id }}">
                                    {{ $m->name }} (Disponible en Almacén: {{ number_format($avail, 1) }} sacos)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label text-white fw-bold small">Sacos a Enviar:</label>
                            <input type="number" step="0.5" min="0.5" name="sacos_transferred" class="form-control bg-dark text-white border-secondary fw-bold text-center fs-5" value="10" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-white-50 small fw-bold">Kg por Saco:</label>
                            <input type="number" step="0.1" name="kg_per_saco" class="form-control bg-dark text-white border-secondary fw-bold text-center fs-5" value="25.0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Notas u Observación:</label>
                        <input type="text" name="notes" class="form-control bg-dark text-white border-secondary" placeholder="Ej. Entrega para turno diurno extrusora 1">
                    </div>
                </div>
                <div class="modal-footer border-secondary py-2">
                    <button type="button" class="btn btn-secondary btn-sm fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">🚀 Confirmar Transferencia</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PARA INICIALIZAR / AJUSTAR STOCK --}}
<div class="modal fade" id="initStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-white" style="background: #1e293b; border: 1px solid #10b981; border-radius: 14px;">
            <div class="modal-header border-secondary py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <span>⚙️</span> Ajuste / Inicialización de Stock
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('raw_materials.stocks.init') }}" method="POST">
                @csrf
                <input type="hidden" name="raw_material_id" id="initModalMaterialId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Insumo:</label>
                        <input type="text" id="initModalMaterialName" class="form-control bg-dark text-white border-secondary fw-bold" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Almacén a Modificar:</label>
                        <select name="warehouse_type" class="form-select bg-dark text-white border-secondary" required>
                            <option value="principal">Almacén Principal (Central)</option>
                            <option value="planta">Depósito de Planta (Pie de Tolva)</option>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label text-white fw-bold small">Número de Sacos:</label>
                            <input type="number" step="0.5" min="0" name="sacos_count" class="form-control bg-dark text-white border-secondary fw-bold text-center fs-5" value="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-white-50 small fw-bold">Kg por Saco:</label>
                            <input type="number" step="0.1" name="kg_per_saco" class="form-control bg-dark text-white border-secondary fw-bold text-center fs-5" value="25.0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary py-2">
                    <button type="button" class="btn btn-secondary btn-sm fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-3">💾 Guardar Existencia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openInitStockModal(id, name) {
        document.getElementById('initModalMaterialId').value = id;
        document.getElementById('initModalMaterialName').value = name;
        new bootstrap.Modal(document.getElementById('initStockModal')).show();
    }
</script>
@endsection
