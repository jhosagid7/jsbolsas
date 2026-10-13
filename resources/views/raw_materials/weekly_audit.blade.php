@extends('layouts.app')
@section('title', 'Arqueo Semanal de Materia Prima y Balance de Masa')

@section('content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-white">⚖️ Arqueo Semanal & Balance de Masa (Corte Sábado)</h3>
            <p class="text-white-50 mb-0">Cruce de materia prima consumida vs. <strong>Bobinas Conformes</strong> + <strong>Merma de Purga</strong>.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('raw_materials.inventory') }}" class="btn btn-outline-info fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Control de Depósitos
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

    {{-- Filtro de Fechas para el Corte --}}
    <div class="card border-0 p-3 mb-4 shadow-sm" style="background: #1e293b; border-radius: 12px; border: 1px solid #334155 !important;">
        <form method="GET" action="{{ route('raw_materials.audit') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label text-white-50 small mb-1">Inicio de Semana / Ciclo:</label>
                <input type="date" name="start_date" class="form-control bg-dark text-white border-secondary" value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label text-white-50 small mb-1">Cierre / Sábado de Arqueo:</label>
                <input type="date" name="end_date" class="form-control bg-dark text-white border-secondary" value="{{ $endDate->format('Y-m-d') }}">
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-info w-100 fw-bold">
                    🔍 Filtrar Datos del Período
                </button>
            </div>
        </form>
    </div>

    {{-- Tarjetas KPI del Período Semanal --}}
    <div class="row g-3 mb-4">
        {{-- Kilos Transferidos --}}
        <div class="col-12 col-md-4">
            <div class="card border-0 p-3 shadow-sm h-100" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-left: 5px solid #3b82f6 !important; border-radius: 12px;">
                <span class="text-uppercase fw-bold text-info small">1. ENVIADO A PLANTA (SEMANA)</span>
                <h3 class="text-white fw-bold mb-0 mt-1 font-monospace">
                    {{ number_format($transferredToPlantKg, 2) }} <span class="fs-6 text-white-50">Kg</span>
                </h3>
                <span class="text-white-50 small">Total de resina movilizada del almacén central</span>
            </div>
        </div>

        {{-- Producción Conforme --}}
        <div class="col-12 col-md-4">
            <div class="card border-0 p-3 shadow-sm h-100" style="background: linear-gradient(135deg, #064e3b 0%, #0f766e 100%); border-left: 5px solid #10b981 !important; border-radius: 12px;">
                <span class="text-uppercase fw-bold text-white-50 small">2. BOBINAS BUENAS (CONFORMES)</span>
                <h3 class="text-white fw-bold mb-0 mt-1 font-monospace">
                    {{ number_format($goodProductionKg, 2) }} <span class="fs-6 text-white-50">Kg</span>
                </h3>
                <span class="text-white-50 small">Producción útil pesada y sellada en turnos</span>
            </div>
        </div>

        {{-- Merma de Purga --}}
        <div class="col-12 col-md-4">
            <div class="card border-0 p-3 shadow-sm h-100" style="background: linear-gradient(135deg, #78350f 0%, #92400e 100%); border-left: 5px solid #f59e0b !important; border-radius: 12px;">
                <span class="text-uppercase fw-bold text-white-50 small">3. MERMA / PURGA DE CALIBRACIÓN</span>
                <h3 class="text-white fw-bold mb-0 mt-1 font-monospace">
                    {{ number_format($wastePurgaKg, 2) }} <span class="fs-6 text-white-50">Kg</span>
                </h3>
                <span class="text-white-50 small">Descarte de boquilla y arranque registrado</span>
            </div>
        </div>
    </div>

    {{-- Formulario para Ejecutar Arqueo del Sábado --}}
    <div class="card border-0 shadow mb-4" style="background: #1e293b; border-radius: 14px; border: 1px solid #eab308 !important;">
        <div class="card-header bg-transparent border-warning border-opacity-25 py-3">
            <h5 class="fw-bold mb-0 text-warning d-flex align-items-center gap-2">
                <span>📝</span> Hoja de Cálculo: Balance de Masa de los Sábados
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('raw_materials.audit.store') }}" method="POST" id="auditForm">
                @csrf
                <input type="hidden" name="audit_date" value="{{ now()->format('Y-m-d') }}">
                <input type="hidden" name="period_start" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" name="period_end" value="{{ $endDate->format('Y-m-d') }}">
                <input type="hidden" name="transferred_to_plant_kg" id="transferredInput" value="{{ $transferredToPlantKg }}">
                <input type="hidden" name="good_production_kg" id="goodProdInput" value="{{ $goodProductionKg }}">
                <input type="hidden" name="waste_purga_kg" id="wastePurgaInput" value="{{ $wastePurgaKg }}">

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white fw-bold small">Stock Inicial en Planta (Kg):</label>
                        <input type="number" step="0.01" name="initial_plant_kg" id="initialPlantInput"
                               class="form-control bg-dark text-white border-secondary fw-bold fs-5 text-center"
                               value="0.00" oninput="recalcBalance()">
                        <small class="text-white-50">Lo que quedó en planta el sábado anterior</small>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label text-white fw-bold small">Transferido en la Semana (Kg):</label>
                        <input type="text" class="form-control bg-dark text-info border-secondary fw-bold fs-5 text-center"
                               value="{{ number_format($transferredToPlantKg, 2) }} Kg" readonly disabled>
                        <small class="text-white-50">Suma de envíos desde Almacén Principal</small>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label text-warning fw-bold small">Conteo Físico Final en Planta (Kg):</label>
                        <input type="number" step="0.01" name="final_counted_plant_kg" id="finalPlantInput"
                               class="form-control bg-dark text-warning border-warning fw-bold fs-5 text-center"
                               value="{{ $currentPlantKg }}" oninput="recalcBalance()" required>
                        <small class="text-white-50">Lo que queda físicamente a pie de tolva hoy</small>
                    </div>
                </div>

                {{-- Monitor Dinámico de Balance de Masa --}}
                <div class="mt-4 p-3 rounded-3 border" id="balanceCard" style="background: #0f172a; border-color: #334155 !important;">
                    <div class="row g-3 text-center align-items-center">
                        <div class="col-12 col-md-3">
                            <span class="text-white-50 small d-block">Consumo Teórico Calculado:</span>
                            <strong class="text-white font-monospace fs-4" id="txtConsumed">0.00 Kg</strong>
                        </div>
                        <div class="col-12 col-md-3">
                            <span class="text-white-50 small d-block">Salidas Justificadas (Bobinas + Purga):</span>
                            <strong class="text-info font-monospace fs-4" id="txtJustified">
                                {{ number_format($goodProductionKg + $wastePurgaKg, 2) }} Kg
                            </strong>
                        </div>
                        <div class="col-12 col-md-3">
                            <span class="text-white-50 small d-block">Diferencia / Descuadre:</span>
                            <strong class="text-success font-monospace fs-4" id="txtVariance">0.00 Kg</strong>
                        </div>
                        <div class="col-12 col-md-3">
                            <span class="text-white-50 small d-block">Estado de Balance:</span>
                            <span class="badge bg-success fs-6" id="badgeBalance">CUADRADO</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label text-white-50 small fw-bold">Observaciones / Conclusiones del Arqueo:</label>
                    <textarea name="notes" class="form-control bg-dark text-white border-secondary" rows="2" placeholder="Ej. El turno cerró con balance óptimo dentro del margen de tolerancia del 2%"></textarea>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-warning btn-lg fw-bold px-4 shadow">
                        ⚖️ Aprobar y Cerrar Arqueo del Sábado
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Histórico de Arqueos Previos --}}
    <div class="card border-0 shadow-sm" style="background: #1e293b; border-radius: 14px; border: 1px solid #334155 !important;">
        <div class="card-header bg-transparent border-secondary py-3">
            <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                <span>📚</span> Histórico de Arqueos y Balances Guardados
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="text-white-50 small text-uppercase" style="background: #0f172a;">
                    <tr>
                        <th class="ps-3">Fecha Corte</th>
                        <th>Período</th>
                        <th class="text-center">Consumo (Kg)</th>
                        <th class="text-center">Bobinas (Kg)</th>
                        <th class="text-center">Purga (Kg)</th>
                        <th class="text-center">Diferencia</th>
                        <th>Auditor</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($audits as $au)
                        <tr>
                            <td class="ps-3 font-monospace text-white fw-bold">
                                {{ $au->audit_date->format('d/m/Y') }}
                            </td>
                            <td class="text-white-50 small">
                                {{ $au->period_start->format('d/m') }} - {{ $au->period_end->format('d/m') }}
                            </td>
                            <td class="text-center font-monospace fw-bold text-info">
                                {{ number_format($au->consumed_kg, 2) }}
                            </td>
                            <td class="text-center font-monospace text-success">
                                {{ number_format($au->good_production_kg, 2) }}
                            </td>
                            <td class="text-center font-monospace text-warning">
                                {{ number_format($au->waste_purga_kg, 2) }}
                            </td>
                            <td class="text-center font-monospace fw-bold {{ abs($au->variance_kg) > 10 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($au->variance_kg, 2) }} Kg ({{ $au->variance_percentage }}%)
                            </td>
                            <td class="text-white-50 small">
                                {{ $au->auditor->name ?? 'Supervisor' }}
                            </td>
                            <td class="text-white-50 small">
                                {{ $au->notes ?: 'Sin notas' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-white-50">
                                Aún no hay arqueos semanales registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function recalcBalance() {
        const initial = parseFloat(document.getElementById('initialPlantInput').value) || 0;
        const transferred = parseFloat(document.getElementById('transferredInput').value) || 0;
        const finalCounted = parseFloat(document.getElementById('finalPlantInput').value) || 0;
        const goodProd = parseFloat(document.getElementById('goodProdInput').value) || 0;
        const wastePurga = parseFloat(document.getElementById('wastePurgaInput').value) || 0;

        const consumed = Math.max(0, (initial + transferred) - finalCounted);
        const justified = goodProd + wastePurga;
        const variance = consumed - justified;
        const variancePercent = consumed > 0 ? ((variance / consumed) * 100).toFixed(1) : '0.0';

        document.getElementById('txtConsumed').textContent = consumed.toFixed(2) + ' Kg';
        document.getElementById('txtVariance').textContent = (variance > 0 ? '+' : '') + variance.toFixed(2) + ' Kg (' + variancePercent + '%)';

        const badge = document.getElementById('badgeBalance');
        const card = document.getElementById('balanceCard');

        if (Math.abs(variance) <= 5.0) {
            badge.className = 'badge bg-success fs-6';
            badge.textContent = '🟢 CUADRADO ÓPTIMO';
            card.style.borderColor = '#10b981';
        } else if (variance > 5.0) {
            badge.className = 'badge bg-danger fs-6';
            badge.textContent = '🔴 FALTANTE DE MATERIAL';
            card.style.borderColor = '#ef4444';
        } else {
            badge.className = 'badge bg-warning text-dark fs-6';
            badge.textContent = '🟡 SOBRANTE DE MATERIAL';
            card.style.borderColor = '#f59e0b';
        }
    }

    document.addEventListener('DOMContentLoaded', recalcBalance);
</script>
@endsection
