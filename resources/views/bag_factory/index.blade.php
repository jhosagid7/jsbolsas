@extends('layouts.theme.app')

@section('content')
<div class="layout-px-spacing">
    <div class="row layout-top-spacing">
        
        <!-- Header -->
        <div class="col-12 mb-4">
            <div class="widget-content widget-content-area br-6 p-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h3 class="font-weight-bold text-white mb-1">🏭 JSBolsas - Control de Fábrica y Supervisión</h3>
                        <p class="text-white-50 mb-0">Auditoría en báscula, aprobación de Pre-Levantamiento y monitoreo en vivo de operarios de planta.</p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <span class="badge badge-info py-2 px-3 mr-2">🟢 Servidor VPS Conectado</span>
                        <a href="{{ route('system.bag_factory.index') }}" class="btn btn-outline-light btn-sm">🔄 Actualizar Monitor</a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('status'))
            <div class="col-12 mb-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>✅ Éxito:</strong> {{ session('status') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- KPI Cards -->
        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-12 mb-4">
            <div class="widget widget-card-four p-4" style="background: #1e293b; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
                <div class="w-content">
                    <div class="w-info">
                        <h6 class="text-white-50 font-weight-bold">BULTOS PENDIENTES DE AUDITAR</h6>
                        <p class="text-warning font-weight-bold" style="font-size: 28px;">{{ count($pendingProductions) }} bultos <small class="text-white-50" style="font-size: 14px;">({{ number_format($totalPendingWeight, 2) }} Kg)</small></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-12 mb-4">
            <div class="widget widget-card-four p-4" style="background: #1e293b; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
                <div class="w-content">
                    <div class="w-info">
                        <h6 class="text-white-50 font-weight-bold">STOCK EN PRE-LEVANTAMIENTO</h6>
                        <p class="text-success font-weight-bold" style="font-size: 28px;">{{ number_format($totalApprovedPkgs, 0) }} bultos <small class="text-white-50" style="font-size: 14px;">({{ number_format($totalApprovedWeight, 2) }} Kg)</small></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-12 mb-4">
            <div class="widget widget-card-four p-4" style="background: #1e293b; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
                <div class="w-content">
                    <div class="w-info">
                        <h6 class="text-white-50 font-weight-bold">TURNOS ACTIVOS EN PLANTA</h6>
                        <p class="text-info font-weight-bold" style="font-size: 28px;">{{ count($activeShifts) }} operarios</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Active Shifts -->
        <div class="col-12 mb-4">
            <div class="widget-content widget-content-area br-6 p-4" style="background: #1e293b; color: #fff; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
                <h5 class="font-weight-bold text-white mb-3">☀️🌙 Turnos Activos en Planta</h5>
                @if($activeShifts->isEmpty())
                    <div class="p-3 text-center text-white-50">No hay operarios con turno abierto en este momento.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead>
                                <tr style="color: #38bdf8;">
                                    <th>Operario</th>
                                    <th>Turno</th>
                                    <th>Hora Inicio</th>
                                    <th>Bultos Registrados</th>
                                    <th>Kilos Producidos</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeShifts as $shift)
                                    <tr>
                                        <td class="font-weight-bold">{{ $shift->effective_user->name ?? $shift->user->name ?? 'Operario' }}</td>
                                        <td>
                                            @if($shift->shift_type === 'diurno')
                                                <span class="badge badge-primary">☀️ Diurno</span>
                                            @else
                                                <span class="badge badge-secondary" style="background: #7c3aed;">🌙 Nocturno</span>
                                            @endif
                                        </td>
                                        <td>{{ $shift->start_time ? $shift->start_time->format('d/m/Y h:i A') : '-' }}</td>
                                        <td class="text-warning font-weight-bold">{{ number_format($shift->total_packages, 0) }}</td>
                                        <td class="text-success font-weight-bold">{{ number_format($shift->total_weight, 2) }} Kg</td>
                                        <td><span class="badge badge-success">En Curso</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pending Review Productions (Scale Audit) -->
        <div class="col-12 mb-4">
            <div class="widget-content widget-content-area br-6 p-4" style="background: #1e293b; color: #fff; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="font-weight-bold text-warning mb-0">⚖️ Bandeja de Auditoría en Báscula (Pendientes de Aprobación)</h5>
                    @if($pendingProductions->isNotEmpty())
                        <form action="{{ route('system.bag_factory.bulk_approve') }}" method="POST" onsubmit="return confirm('¿Aprobar todos los bultos pendientes para Pre-Levantamiento?');">
                            @csrf
                            @foreach($pendingProductions as $p)
                                <input type="hidden" name="ids[]" value="{{ $p->id }}">
                            @endforeach
                            <button type="submit" class="btn btn-success btn-sm">✅ Aprobar Todos ({{ count($pendingProductions) }})</button>
                        </form>
                    @endif
                </div>

                @if($pendingProductions->isEmpty())
                    <div class="p-4 text-center text-white-50">
                        🎉 ¡Excelente! No hay bultos pendientes de revisión en báscula.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead>
                                <tr style="color: #38bdf8;">
                                    <th>Fecha / Hora</th>
                                    <th>Operario</th>
                                    <th>Producto / Bolsa</th>
                                    <th>Bultos</th>
                                    <th>Peso (Kg)</th>
                                    <th class="text-right">Acciones de Auditoría</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingProductions as $prod)
                                    <tr>
                                        <td>{{ $prod->recorded_at ? $prod->recorded_at->format('d/m/Y h:i A') : '-' }}</td>
                                        <td class="font-weight-bold">{{ $prod->user->name ?? 'Operario' }}</td>
                                        <td class="text-info font-weight-bold">{{ $prod->product->name ?? 'Bolsa' }}</td>
                                        <td>{{ number_format($prod->quantity, 0) }}</td>
                                        <td>
                                            <span class="text-warning font-weight-bold" style="font-size: 16px;">{{ number_format($prod->weight, 2) }} Kg</span>
                                            @if($prod->original_weight)
                                                <br><small class="text-muted"><del>{{ number_format($prod->original_weight, 2) }} Kg</del></small>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <!-- Adjust Weight Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#adjustModal{{ $prod->id }}">
                                                ⚖️ Báscula
                                            </button>

                                            <!-- Reject Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal{{ $prod->id }}">
                                                ❌ Rechazar
                                            </button>

                                            <!-- Approve Form -->
                                            <form action="{{ route('system.bag_factory.approve', $prod->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">✅ Aprobar</button>
                                            </form>

                                            <a href="{{ route('system.bag_factory.ticket', $prod->id) }}" target="_blank" class="btn btn-sm btn-secondary">🖨️ Ticket</a>
                                        </td>
                                    </tr>

                                    <!-- Adjust Modal -->
                                    <div class="modal fade" id="adjustModal{{ $prod->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content" style="background: #0f172a; color: #fff;">
                                                <form action="{{ route('system.bag_factory.adjust', $prod->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title text-white">⚖️ Corrección de Pesaje en Báscula</h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-white-50 mb-3">{{ $prod->product->name ?? 'Bolsa' }} - Operario: {{ $prod->user->name ?? 'Operario' }}</p>
                                                        <div class="form-group">
                                                            <label>Peso Real en Báscula (Kg):</label>
                                                            <input type="number" step="0.0001" name="weight" class="form-control" value="{{ $prod->weight }}" required style="font-size: 18px; font-weight: bold; color: #f59e0b; background: #1e293b; border-color: #334155;">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-info">Guardar Peso</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal{{ $prod->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content" style="background: #0f172a; color: #fff;">
                                                <form action="{{ route('system.bag_factory.reject', $prod->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title text-danger">❌ Rechazar Bulto</h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Motivo del Rechazo:</label>
                                                            <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Ej: Bolsa perforada, micraje irregular o mal sellada" required style="background: #1e293b; color: #fff; border-color: #334155;"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-danger">Rechazar Bulto</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pre-Stock List -->
        <div class="col-12 mb-4">
            <div class="widget-content widget-content-area br-6 p-4" style="background: #1e293b; color: #fff; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);">
                <h5 class="font-weight-bold text-success mb-3">📦 Stock Aprobado en Pre-Levantamiento (Listo para Almacén General)</h5>
                @if($preStockProductions->isEmpty())
                    <div class="p-3 text-center text-white-50">No hay bultos aprobados en Pre-Levantamiento.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead>
                                <tr style="color: #38bdf8;">
                                    <th>Código QR</th>
                                    <th>Producto</th>
                                    <th>Operario</th>
                                    <th>Bultos</th>
                                    <th>Peso Total</th>
                                    <th>Auditado Por</th>
                                    <th>Fecha Aprobación</th>
                                    <th class="text-right">Ticket</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($preStockProductions as $item)
                                    <tr>
                                        <td><span class="badge badge-info font-weight-bold">{{ $item->qr_code }}</span></td>
                                        <td class="font-weight-bold">{{ $item->product->name ?? 'Bolsa' }}</td>
                                        <td>{{ $item->user->name ?? 'Operario' }}</td>
                                        <td>{{ number_format($item->quantity, 0) }}</td>
                                        <td class="text-success font-weight-bold">{{ number_format($item->weight, 2) }} Kg</td>
                                        <td>{{ $item->reviewer->name ?? 'Supervisor' }}</td>
                                        <td>{{ $item->reviewed_at ? $item->reviewed_at->format('d/m/Y h:i A') : '-' }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('system.bag_factory.ticket', $item->id) }}" target="_blank" class="btn btn-sm btn-outline-light">🖨️ Imprimir</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $preStockProductions->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection