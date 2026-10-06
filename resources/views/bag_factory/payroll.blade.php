@extends('layouts.app')
@section('title', 'Monitor de Nómina y Rendimiento de Operarios')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-1">💵 Nómina y Rendimiento por Metas</h3>
        <p class="text-white-50 mb-0">Cálculo dinámico por salario semanal, meta por turno, bultos completos liberados y fracciones retenidas.</p>
    </div>
    <form method="GET" action="{{ route('bag_factory.payroll') }}" class="d-flex gap-2 align-items-center">
        <input type="date" name="start_date" class="form-control form-control-sm bg-secondary text-white border-0" value="{{ $startDate->toDateString() }}">
        <span class="text-white-50">al</span>
        <input type="date" name="end_date" class="form-control form-control-sm bg-secondary text-white border-0" value="{{ $endDate->toDateString() }}">
        <button type="submit" class="btn btn-primary btn-sm fw-bold">Filtrar</button>
    </form>
</div>

<!-- Resumen Global de Nómina -->
@php
    $totalEarned = $payrollData->sum(fn($p) => $p['weekly']['earned']);
    $totalAvailable = $payrollData->sum(fn($p) => $p['weekly']['available']);
    $totalRetained = $payrollData->sum(fn($p) => $p['weekly']['retained']);
    $totalPackages = $payrollData->sum(fn($p) => $p['weekly']['completed_packages']);
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3">
            <div class="text-white-50 small">Total Devengado (Semana)</div>
            <div class="h3 fw-bold text-info mb-0">${{ number_format($totalEarned, 2) }} USD</div>
            <small class="text-white-50">Producción total registrada</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3">
            <div class="text-white-50 small">🟢 Disponible para Cobro</div>
            <div class="h3 fw-bold text-success mb-0">${{ number_format($totalAvailable, 2) }} USD</div>
            <small class="text-white-50">Por bultos completos cerrados</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3">
            <div class="text-white-50 small">⏳ Retenido por Fracciones</div>
            <div class="h3 fw-bold text-warning mb-0">${{ number_format($totalRetained, 2) }} USD</div>
            <small class="text-white-50">Pendiente de completar bulto</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3">
            <div class="text-white-50 small">Bultos Cerrados</div>
            <div class="h3 fw-bold text-primary mb-0">{{ number_format($totalPackages, 0) }} Pkgs</div>
            <small class="text-white-50">En el período seleccionado</small>
        </div>
    </div>
</div>

<!-- Tabla Detallada de Operarios -->
<div class="card bg-dark border-secondary mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-white">👷 Nómina Individual por Operario</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
                <tr class="text-white-50 small">
                    <th>Operario</th>
                    <th>Base Pactada</th>
                    <th>Días / Sem</th>
                    <th>Salario Diario</th>
                    <th>Bultos Cerrados</th>
                    <th>Fracciones (Mill)</th>
                    <th>Devengado ($)</th>
                    <th>🟢 Disponible ($)</th>
                    <th>⏳ Retenido ($)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrollData as $row)
                    @php
                        $op = $row['operator'];
                        $w = $row['weekly'];
                    @endphp
                    <tr>
                        <td class="fw-bold text-white">
                            <i class="bi bi-person-fill text-info me-1"></i> {{ $op->name }}
                        </td>
                        <td>${{ number_format($w['weekly_salary'], 2) }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $w['work_days'] }} Días</span>
                        </td>
                        <td class="text-info fw-bold">${{ number_format($w['daily_salary'], 2) }}/día</td>
                        <td class="fw-bold">{{ number_format($w['completed_packages'], 0) }}</td>
                        <td>{{ number_format($w['fractional_units'], 0) }}</td>
                        <td class="fw-bold text-info">${{ number_format($w['earned'], 2) }}</td>
                        <td class="fw-bold text-success">${{ number_format($w['available'], 2) }}</td>
                        <td class="fw-bold text-warning">${{ number_format($w['retained'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-white-50 py-4">No se encontraron datos de operarios para este período.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Fracciones Incompletas Pendientes de Cierre Colaborativo -->
<div class="card bg-dark border-secondary">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-warning">🧩 Fracciones Sueltas Pendientes de Cerrar Bulto</h5>
            <small class="text-white-50">Al completarse con producción de otro turno u operario, se libera automáticamente el sueldo retenido.</small>
        </div>
        <span class="badge bg-warning text-dark">{{ $incompleteFractions->count() }} Pendiente(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
                <tr class="text-white-50 small">
                    <th>Fecha</th>
                    <th>Código QR</th>
                    <th>Lote</th>
                    <th>Operario Original</th>
                    <th>Producto</th>
                    <th>Fracción Suelta</th>
                    <th>Monto Retenido</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incompleteFractions as $frac)
                    <tr>
                        <td>{{ $frac->recorded_at ? $frac->recorded_at->format('d/m/Y h:i A') : '-' }}</td>
                        <td><code>{{ $frac->qr_code }}</code></td>
                        <td><span class="badge bg-dark border border-secondary">{{ $frac->effective_batch_code }}</span></td>
                        <td class="fw-bold text-white">{{ $frac->user->name ?? 'Operario' }}</td>
                        <td>{{ $frac->product_name }}</td>
                        <td class="text-warning fw-bold">{{ number_format($frac->fractional_units, 0) }} Millares</td>
                        <td class="text-warning fw-bold">${{ number_format($frac->labor_retained_amount, 2) }} USD</td>
                        <td>
                            <span class="badge bg-warning text-dark">⏳ Retenido</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-white-50 py-3">No hay fracciones sueltas pendientes. ¡Todos los bultos están cerrados!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
