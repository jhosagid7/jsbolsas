@extends('layouts.app')
@section('title', 'Estación Web de Operarios - JSBolsas Pro')

@section('content')
<div class="container-fluid px-2 px-md-3">

    {{-- Encabezado Principal y Selector de Operador --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                <span>⚡</span> Estación de Carga & Báscula
            </h3>
            <p class="text-white-50 mb-0 small">
                Operador: <strong class="text-info">{{ $targetUser->name }}</strong>
                @if($activeShift)
                    • Máquina: <span class="badge bg-primary text-white">{{ $activeShift->machine->name ?? 'Sin Asignar' }}</span>
                    • Jornada: <span class="badge bg-secondary text-uppercase">{{ $activeShift->shift_type }}</span>
                    • Inicio: <span class="text-info font-monospace">{{ $activeShift->start_time->format('h:i A') }}</span>
                @endif
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if(!$user->isOperator() && $operatorsList->isNotEmpty())
                <form method="GET" action="{{ route('operator.station') }}" class="d-flex align-items-center gap-1">
                    <label class="text-white-50 small mb-0 d-none d-sm-inline">Ver Operador:</label>
                    <select name="operator_id" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()">
                        <option value="{{ $user->id }}" {{ $targetUser->id == $user->id ? 'selected' : '' }}>
                            👑 Mi Estación ({{ $user->name }})
                        </option>
                        @foreach($operatorsList as $op)
                            <option value="{{ $op->id }}" {{ $targetUser->id == $op->id ? 'selected' : '' }}>
                                👤 {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if($activeShift)
                <form action="{{ route('operator.close_shift') }}" method="POST" onsubmit="return confirm('¿Seguro que deseas cerrar tu turno de trabajo actual?');">
                    @csrf
                    <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">
                    @if(!$user->isOperator())
                        <input type="hidden" name="operator_id" value="{{ $targetUser->id }}">
                    @endif
                    <button type="submit" class="btn btn-outline-danger btn-sm fw-bold d-flex align-items-center gap-1">
                        <i class="bi bi-door-closed"></i> Cerrar Turno
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Alertas de Sesión --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show bg-success text-white border-0 py-2 mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show bg-info text-dark border-0 py-2 mb-3" role="alert">
            <i class="bi bi-info-circle-fill me-1"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show bg-danger text-white border-0 py-2 mb-3" role="alert">
            <strong>⚠️ Atención:</strong>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tarjetas KPI de Rendimiento y Ganancias en USD (Idénticas a la App Móvil) --}}
    <div class="row g-2 mb-3">
        {{-- Banner de Ganancia en USD --}}
        <div class="col-12 col-md-5">
            <div class="card border-0 p-3 h-100 shadow-sm" style="background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #0f766e 100%); border-radius: 14px; border: 1px solid rgba(52, 211, 153, 0.3) !important;">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15); width: 34px; height: 34px;">
                            <span class="text-white fw-bold fs-6">💵</span>
                        </div>
                        <div>
                            <span class="text-uppercase fw-bold text-white-50" style="font-size: 11px; letter-spacing: 0.5px;">MI GANANCIA DEL TURNO</span>
                            <div class="h2 fw-bolder text-white mb-0 font-monospace" style="letter-spacing: -0.5px;">
                                ${{ number_format($totalEarnedUsd, 2) }} <span class="fs-6 fw-normal text-white-50">USD</span>
                            </div>
                        </div>
                    </div>
                    <span class="badge bg-success text-white fw-bold px-2 py-1 font-monospace" style="font-size: 10px;">
                        Base: ${{ number_format($targetUser->daily_salary, 2) }}/día
                    </span>
                </div>
                <div class="d-flex justify-content-between text-white-50 small mt-2 pt-2 border-top border-white-subtle" style="font-size: 11px;">
                    <span>Jornada: {{ $targetUser->work_days_per_week ?: 6 }} días/sem</span>
                    <span>Salario Semanal: ${{ number_format($targetUser->weekly_salary ?: 90, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Meta Combinada Multimedida --}}
        <div class="col-12 col-md-4">
            <div class="card border-0 p-3 h-100 shadow-sm" style="background: #1e293b; border-radius: 14px; border: 1px solid #334155 !important;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-uppercase fw-bold text-white-50" style="font-size: 11px; letter-spacing: 0.5px;">META DE TURNO COMBINADA</span>
                    @if($combinedProgressPercent >= 100)
                        <span class="badge bg-success text-white fw-bold">🎉 ¡META CUMPLIDA!</span>
                    @elseif($combinedProgressPercent >= 50)
                        <span class="badge bg-warning text-dark fw-bold">🚀 BUEN RITMO</span>
                    @else
                        <span class="badge bg-info text-dark fw-bold">⚡ EN CURSO</span>
                    @endif
                </div>
                <div class="h3 fw-bold text-white font-monospace mb-1">
                    {{ $combinedProgressPercent }}%
                </div>
                <div class="progress bg-dark mb-1" style="height: 10px; border-radius: 6px;">
                    <div class="progress-bar {{ $combinedProgressPercent >= 100 ? 'bg-success' : ($combinedProgressPercent >= 50 ? 'bg-warning' : 'bg-info') }} progress-bar-striped progress-bar-animated"
                         role="progressbar" style="width: {{ min(100, $combinedProgressPercent) }}%"></div>
                </div>
                <small class="text-white-50" style="font-size: 11px;">
                    Deducción proporcional automática por cambio de medida en planta.
                </small>
            </div>
        </div>

        {{-- Total Fardos / Kilos --}}
        <div class="col-12 col-md-3">
            <div class="card border-0 p-3 h-100 shadow-sm" style="background: #1e293b; border-radius: 14px; border: 1px solid #334155 !important;">
                <span class="text-uppercase fw-bold text-white-50" style="font-size: 11px; letter-spacing: 0.5px;">TOTAL FABRICADO HOY</span>
                <div class="h3 fw-bold text-info font-monospace mb-0 mt-1">
                    {{ number_format($totalUnits, 0) }} <span class="fs-6 text-white-50">Millares</span>
                </div>
                <div class="text-white-50 small mt-1">
                    ⚖️ <strong>{{ number_format($totalWeightKg, 2) }} Kg</strong> procesados
                </div>
            </div>
        </div>
    </div>

    {{-- CONDICIONAL: ABRIR TURNO O CARGA DE PRODUCCIÓN --}}
    @if(!$activeShift)
        {{-- Banner para Iniciar Turno si no hay ninguno activo --}}
        <div class="card border-0 p-4 text-center shadow-lg my-4" style="background: #1e293b; border-radius: 16px; border: 2px dashed #0284c7 !important;">
            <div class="mb-3">
                <span style="font-size: 45px;">🏭</span>
                <h4 class="fw-bold text-white mt-2">No tienes un turno activo en este momento</h4>
                <p class="text-white-50 mb-3">Selecciona la máquina que operarás para comenzar tu registro de producción y acumulación de nómina.</p>
            </div>

            <form action="{{ route('operator.open_shift') }}" method="POST" class="mx-auto" style="max-width: 450px;">
                @csrf
                @if(!$user->isOperator())
                    <input type="hidden" name="operator_id" value="{{ $targetUser->id }}">
                @endif
                <div class="mb-3 text-start">
                    <label class="form-label text-white fw-bold small">Máquina Asignada:</label>
                    <select name="machine_id" class="form-select form-select-lg bg-dark text-white border-secondary" required>
                        <option value="">-- Selecciona tu máquina --</option>
                        @foreach($machines as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4 text-start">
                    <label class="form-label text-white fw-bold small">Tipo de Turno:</label>
                    <div class="d-flex gap-2">
                        <input type="radio" class="btn-check" name="shift_type" id="shift_diurno" value="diurno" checked>
                        <label class="btn btn-outline-info w-50 py-2 fw-bold" for="shift_diurno">☀️ Diurno</label>

                        <input type="radio" class="btn-check" name="shift_type" id="shift_nocturno" value="nocturno">
                        <label class="btn btn-outline-info w-50 py-2 fw-bold" for="shift_nocturno">🌙 Nocturno</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow py-3">
                    🚀 INICIAR TURNO DE TRABAJO
                </button>
            </form>
        </div>
    @else
        {{-- ESTACIÓN DE CARGA RÁPIDA (TOUCH FRIENDLY) --}}
        <div class="card border-0 p-3 p-md-4 mb-4 shadow" style="background: #1e293b; border-radius: 16px; border: 1px solid #334155 !important;">
            <form action="{{ route('operator.store_batch') }}" method="POST" id="batchProductionForm">
                @csrf
                <input type="hidden" name="machine_id" value="{{ $activeShift->machine_id }}">
                <input type="hidden" name="print_mode" id="printModeInput" value="direct_print">
                @if(!$user->isOperator())
                    <input type="hidden" name="operator_id" value="{{ $targetUser->id }}">
                @endif

                @php
                    $isExtrusionMachine = ($activeShift->machine && $activeShift->machine->type === 'extrusora');
                @endphp

                @if($isExtrusionMachine)
                    {{-- PANEL DE CONTROL DE EXTRUSIÓN: RECETA Y MERMA DE PURGA --}}
                    <div class="p-3 mb-3 rounded-3 border" style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%); border-color: rgba(56, 189, 248, 0.4) !important;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-info text-dark fw-bold px-2 py-1 fs-6">🌀 MÓDULO EXTRUSORA</span>
                                <span class="text-white small fw-bold">Manga Continua & Bobinado</span>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm fw-bold d-flex align-items-center gap-1 shadow-sm"
                                    data-bs-toggle="modal" data-bs-target="#wasteRecordModal">
                                <span>⚠️</span> Registrar Merma / Purga de Calibración
                            </button>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-7">
                                <label class="form-label text-white-50 small mb-1 fw-bold">
                                    🧪 Fórmula / Receta de Mezcla Activa:
                                </label>
                                <select name="production_formula_id" id="productionFormulaSelect" class="form-select bg-dark text-white border-info fw-bold">
                                    <option value="">-- Usar Fórmula Predeterminada del Producto --</option>
                                    @foreach($formulas as $form)
                                        <option value="{{ $form->id }}">
                                            🧪 {{ $form->name }} ({{ $form->code ?? 'S/C' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="small text-white-50 p-2 rounded bg-black bg-opacity-30 border border-secondary mt-md-3">
                                    <span>💡 Las etiquetas reflejarán las dimensiones técnicas en <strong>Centímetros (cm)</strong> y la receta empleada.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- BANDEJA DE BOBINAS DISPONIBLES EN PLANTA PARA SELLADORA --}}
                    <div class="p-3 mb-3 rounded-3 border" style="background: #111827; border-color: #3b82f6 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white fw-bold px-2 py-1 fs-6">✂️ BOBINAS LISTAS PARA SELLADO</span>
                                <span class="text-white-50 small">Bobinas de manga continua listas para montar en portarrollos</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-warning btn-sm fw-bold d-flex align-items-center gap-1 shadow-sm"
                                        data-bs-toggle="modal" data-bs-target="#wasteRecordModal" title="Registrar merma operativa o defecto de material en sellado">
                                    <span>⚠️</span> Registrar Merma / Defecto
                                </button>
                                {{-- Lector / Input Rápido de Código QR / SKU de Bobina --}}
                                <form action="{{ route('operator.mount_bobina') }}" method="POST" class="d-inline-flex gap-1 align-items-center mb-0">
                                    @csrf
                                    @if(!$user->isOperator() && request('operator_id'))
                                        <input type="hidden" name="operator_id" value="{{ request('operator_id') }}">
                                    @endif
                                    <div class="input-group input-group-sm" style="width: 220px;">
                                        <span class="input-group-text bg-dark border-secondary text-warning" title="Escanear QR de Bobina">📷</span>
                                        <input type="text" name="qr_code" class="form-control form-control-sm bg-dark text-white border-secondary font-monospace" placeholder="Escanear QR Bobina..." style="font-size: 11px;">
                                        <button type="submit" class="btn btn-outline-info btn-sm fw-bold">Montar</button>
                                    </div>
                                </form>
                                <span class="badge bg-dark border border-secondary text-info fw-normal">
                                    {{ $availableBobinas->count() }} bobina(s) en piso
                                </span>
                            </div>
                        </div>

                        {{-- BANNER DE BOBINA MONTADA ACTUALMENTE EN LA SELLADORA --}}
                        @if(!empty($mountedBobina))
                            @php
                                $mProduct = $mountedBobina->product;
                                $mWidth = (float)($mProduct?->effective_width_cm ?? 0);
                                $mInitW = (float)($mountedBobina->original_weight ?: $mountedBobina->weight);
                                $mRemW = (float)$mountedBobina->remaining_weight;
                                $mConsW = (float)$mountedBobina->consumed_weight;
                                $mPct = $mInitW > 0 ? min(100, round(($mConsW / $mInitW) * 100)) : 100;
                                $mBadgeClass = $mRemW <= 1.0 ? 'bg-danger text-white' : ($mRemW <= 3.0 ? 'bg-warning text-dark' : 'bg-success text-dark');
                                $mMat = $mProduct?->effective_material_type ?? 'Baja Densidad';
                                $mIsAlta = stripos($mMat, 'ALTA') !== false;
                                $mHasGusset = (bool)($mProduct?->effective_has_gusset ?? false);
                                $mGussetVal = (float)($mProduct?->gusset_cm ?? 0);
                            @endphp
                            <div class="p-3 mb-3 rounded border border-success bg-dark d-flex justify-content-between align-items-center flex-wrap gap-2 shadow-sm" style="background: rgba(16, 185, 129, 0.08) !important;">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-success text-dark fs-6 fw-bold px-2 py-1">
                                        ⚙️ BOBINA MONTADA
                                    </span>
                                    <span class="badge {{ $mIsAlta ? 'bg-primary text-white' : 'bg-info text-dark' }} fs-6 fw-bold">
                                        {{ $mIsAlta ? '🔷 ALTA DENSIDAD' : '🔹 BAJA DENSIDAD' }}
                                    </span>
                                    <span class="badge {{ $mHasGusset ? 'bg-warning text-dark' : 'bg-secondary text-white' }} fs-6 fw-bold">
                                        {{ $mHasGusset ? '📐 CON FUELLE' . ($mGussetVal > 0 ? ' (' . round($mGussetVal, 1) . ' cm)' : '') : '⚪ PLANA' }}
                                    </span>
                                    <span class="badge bg-dark border border-secondary text-white fs-6 font-monospace">
                                        📏 {{ round($mWidth, 1) }} cm
                                    </span>
                                    <span class="badge bg-dark border border-warning text-warning fs-6 font-monospace">
                                        Lote: {{ $mountedBobina->effective_batch_code }}
                                    </span>
                                    <span class="badge {{ $mBadgeClass }} fs-6 fw-bold shadow-sm" title="Saldo disponible para sellar">
                                        Saldo: {{ number_format($mRemW, 2) }} Kg
                                    </span>
                                    <span class="text-white-50 ms-1" style="font-size: 11px;">
                                        (Consumido: {{ number_format($mConsW, 2) }} Kg / Inicial: {{ number_format($mInitW, 2) }} Kg - {{ $mPct }}%)
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button type="button" class="btn btn-warning btn-sm fw-bold py-1 px-2" style="font-size: 11px;"
                                            data-bs-toggle="modal" data-bs-target="#wasteRecordModal" title="Registrar merma o defecto atribuible a esta bobina">
                                        ⚠️ Registrar Merma / Defecto
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm fw-bold py-1 px-2" style="font-size: 11px;" onclick="openProductDropdown()">
                                        🎯 Ver bolsas compatibles ({{ round($mWidth, 1) }} cm)
                                    </button>
                                    <form action="{{ route('operator.unmount_bobina') }}" method="POST" class="d-inline mb-0">
                                        @csrf
                                        @if(!$user->isOperator() && request('operator_id'))
                                             <input type="hidden" name="operator_id" value="{{ request('operator_id') }}">
                                        @endif
                                        <button type="submit" class="btn btn-outline-danger btn-sm fw-bold py-1 px-2" style="font-size: 11px;" title="Desmontar bobina temporalmente y guardarla con su saldo en piso">
                                            ✕ Desmontar
                                        </button>
                                    </form>
                                    <form action="{{ route('operator.mark_bobina_consumed') }}" method="POST" class="d-inline mb-0" onsubmit="return confirm('¿Confirmas que la bobina se terminó por completo (llegó al cono/tubo de cartón)?');">
                                        @csrf
                                        <input type="hidden" name="bobina_id" value="{{ $mountedBobina->id }}">
                                        @if(!$user->isOperator() && request('operator_id'))
                                             <input type="hidden" name="operator_id" value="{{ request('operator_id') }}">
                                        @endif
                                        <button type="submit" class="btn btn-warning btn-sm fw-bold py-1 px-2 text-dark" style="font-size: 11px;" title="Marcar bobina como agotada/terminada">
                                            ✅ Agotar Bobina
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="p-2 mb-2 rounded border border-warning bg-black bg-opacity-40 d-flex justify-content-between align-items-center text-white-50 small">
                                <span>⚠️ <strong>Ninguna bobina montada en esta selladora.</strong> Elige una bobina de la bandeja inferior o escanea su QR para garantizar trazabilidad.</span>
                            </div>
                        @endif

                        @if($availableBobinas->isEmpty())
                            <div class="p-2 text-center text-white-50 small bg-dark rounded border border-secondary">
                                <span>ℹ️ No hay bobinas pendientes de sellado registradas en planta actualmente.</span>
                            </div>
                        @else
                            <div class="d-flex gap-2 overflow-auto pb-2" style="white-space: nowrap;">
                                @foreach($availableBobinas as $bob)
                                    @php
                                        $isThisMounted = !empty($mountedBobina) && $mountedBobina->id === $bob->id;
                                        $bInitW = (float)($bob->original_weight ?: $bob->weight);
                                        $bRemW = (float)$bob->remaining_weight;
                                        $bConsW = (float)$bob->consumed_weight;
                                        $bBadgeClass = $bRemW <= 1.0 ? 'bg-danger text-white' : ($bRemW <= 3.0 ? 'bg-warning text-dark' : 'bg-success text-dark');
                                        $bProduct = $bob->product;
                                        $bMat = $bProduct?->effective_material_type ?? 'Baja Densidad';
                                        $bIsAlta = stripos($bMat, 'ALTA') !== false;
                                        $bHasGusset = (bool)($bProduct?->effective_has_gusset ?? false);
                                        $bGussetVal = (float)($bProduct?->gusset_cm ?? 0);
                                        $bWidth = (float)($bProduct?->effective_width_cm ?? 0);
                                        $bCaliber = (float)($bProduct?->gauge_caliber ?? 0);
                                    @endphp
                                    <div class="card bg-dark border-secondary p-2 shadow-sm text-start {{ $isThisMounted ? 'border-success ring-2' : '' }}" style="min-width: 310px; max-width: 360px; border-radius: 10px;">
                                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <span class="badge {{ $bIsAlta ? 'bg-primary' : 'bg-info text-dark' }} fw-bold" style="font-size: 10px;">
                                                    {{ $bIsAlta ? '🔷 ALTA' : '🔹 BAJA' }}
                                                </span>
                                                <span class="badge {{ $bHasGusset ? 'bg-warning text-dark' : 'bg-secondary' }} fw-bold" style="font-size: 10px;">
                                                    {{ $bHasGusset ? '📐 FUELLE' . ($bGussetVal > 0 ? ' ' . round($bGussetVal, 1) . 'cm' : '') : '⚪ PLANA' }}
                                                </span>
                                                <span class="badge bg-dark border border-secondary text-white fw-bold" style="font-size: 10px;">
                                                    📏 {{ round($bWidth, 1) }} cm
                                                </span>
                                            </div>
                                            <span class="badge {{ $bBadgeClass }} font-monospace" style="font-size: 11px;" title="Saldo restante: {{ number_format($bRemW, 2) }} Kg / Inicial: {{ number_format($bInitW, 2) }} Kg">
                                                Saldo: {{ number_format($bRemW, 2) }} Kg
                                            </span>
                                        </div>
                                        <div class="text-white fw-bold mb-1" style="font-size: 12px;" title="{{ $bProduct?->name ?? 'Bobina' }}">
                                            {{ $bProduct?->name ?? 'Bobina Manga' }}
                                        </div>
                                        <div class="text-white-50 small d-flex flex-column gap-1" style="font-size: 11px;">
                                            <div class="d-flex justify-content-between text-secondary" style="font-size: 10px;">
                                                <span>🏷️ Lote: <code class="text-warning">{{ $bob->effective_batch_code }}</code></span>
                                                <span>({{ number_format($bInitW, 1) }}k orig.)</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center text-white-50 mt-1 pt-1 border-top border-secondary" style="font-size: 10px;">
                                                <span>👤 {{ Str::limit($bob->user->name ?? 'Extrusor', 12) }}</span>
                                                <div class="d-flex gap-1">
                                                    @if($isThisMounted)
                                                        <span class="badge bg-success text-dark fw-bold" style="font-size: 10px;">✓ MONTADA</span>
                                                        <form action="{{ route('operator.unmount_bobina') }}" method="POST" class="d-inline mb-0">
                                                            @csrf
                                                            @if(!$user->isOperator() && request('operator_id'))
                                                                <input type="hidden" name="operator_id" value="{{ request('operator_id') }}">
                                                            @endif
                                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2 fw-bold" style="font-size: 10px;" title="Desmontar bobina de la selladora">
                                                                ✕ Desmontar
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('operator.mark_bobina_consumed') }}" method="POST" class="d-inline mb-0" onsubmit="return confirm('¿Confirmas que la bobina se terminó por completo?');">
                                                            @csrf
                                                            <input type="hidden" name="bobina_id" value="{{ $bob->id }}">
                                                            @if(!$user->isOperator() && request('operator_id'))
                                                                <input type="hidden" name="operator_id" value="{{ request('operator_id') }}">
                                                            @endif
                                                            <button type="submit" class="btn btn-warning btn-sm py-0 px-1 fw-bold text-dark" style="font-size: 10px;" title="Marcar bobina como agotada">
                                                                ✅ Agotar
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('operator.mount_bobina') }}" method="POST" class="d-inline mb-0">
                                                            @csrf
                                                            <input type="hidden" name="bobina_id" value="{{ $bob->id }}">
                                                            @if(!$user->isOperator() && request('operator_id'))
                                                                <input type="hidden" name="operator_id" value="{{ request('operator_id') }}">
                                                            @endif
                                                            <button type="submit" class="btn btn-primary btn-sm py-0 px-2 fw-bold" style="font-size: 10px;" title="Montar esta bobina en el portarrollos">
                                                                ⚙️ Montar
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('ticket', ['id' => $bob->id, 'scope' => 'bulto']) }}" target="_blank"
                                                       class="btn btn-outline-info btn-sm py-0 px-2 fw-bold" style="font-size: 10px;">
                                                        🖨️
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if($isExtrusionMachine)
                    {{-- MODO EXTRUSORA: MANGA CONTINUA & PARÁMETROS TÉCNICOS DIRECTOS --}}
                    <input type="hidden" name="is_extrusion_direct" value="1">
                    <input type="hidden" name="product_id" id="selectedProductId" value="">

                    <div class="row g-3">
                        {{-- Columna 1: Medidas Técnicas de Manga Continua --}}
                        <div class="col-12 col-lg-5">
                            <label class="form-label text-white fw-bold small d-flex justify-content-between align-items-center mb-1">
                                <span>1. Medida de Manga Continua:</span>
                                <span class="badge bg-info text-dark fw-bold" id="extrusionSpecBadge">Manga 22 cm Plana</span>
                            </label>

                            {{-- Botones Rápidos de Anchos Frecuentes en cm --}}
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <span class="text-white-50 small align-self-center me-1" style="font-size: 10px;">Anchos cm:</span>
                                @foreach([15, 18, 20, 22, 25, 30, 35, 40, 50] as $fastWidth)
                                    <button type="button" class="btn btn-outline-info btn-sm py-0 px-2 fw-bold fast-width-btn {{ $fastWidth === 22 ? 'active' : '' }}"
                                            style="font-size: 11px; border-radius: 6px;"
                                            onclick="setExtrusionWidth({{ $fastWidth }})">
                                        {{ $fastWidth }} cm
                                    </button>
                                @endforeach
                            </div>

                            <div class="row g-2 mb-2">
                                {{-- Ancho en cm --}}
                                <div class="col-6">
                                    <label class="form-label text-white-50 small mb-1 fw-bold">Ancho (cm):</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="5" max="250" name="extrusion_width_cm" id="extrusionWidthInput"
                                               class="form-control bg-dark text-white border-info fw-bold fs-6 text-center"
                                               value="22" required oninput="updateExtrusionPreview()">
                                        <span class="input-group-text bg-secondary text-white fw-bold">cm</span>
                                    </div>
                                </div>

                                {{-- Calibre --}}
                                <div class="col-6">
                                    <label class="form-label text-white-50 small mb-1 fw-bold" title="Puedes escribir 15, 3 o 0.0150">
                                        Calibre (C): <span class="text-info" style="font-size: 10px;">(ej: 15, 3)</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" min="0.0001" max="100" name="extrusion_caliber" id="extrusionCaliberInput"
                                               class="form-control bg-dark text-white border-secondary fw-bold fs-6 text-center"
                                               value="3.0" placeholder="15 ó 0.0150" required oninput="updateExtrusionPreview()">
                                        <span class="input-group-text bg-secondary text-white fw-bold">C</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Tipo de Material & Fuelle --}}
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label text-white-50 small mb-1 fw-bold">Material:</label>
                                    <select name="extrusion_material_type" id="extrusionMaterialSelect"
                                            class="form-select form-select-sm bg-dark text-white border-secondary fw-bold"
                                            onchange="updateExtrusionPreview()">
                                        <option value="Baja Densidad" selected>Baja Densidad</option>
                                        <option value="Alta Densidad">Alta Densidad</option>
                                    </select>
                                </div>

                                <div class="col-6">
                                    <label class="form-label text-white-50 small mb-1 fw-bold">Fuelle:</label>
                                    <select name="extrusion_has_gusset" id="extrusionGussetSelect"
                                            class="form-select form-select-sm bg-dark text-white border-secondary fw-bold"
                                            onchange="toggleExtrusionGusset(this.value)">
                                        <option value="0" selected>Sin Fuelle (Plana)</option>
                                        <option value="1">Con Fuelle</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Destino de la Bobina: Para Sellado vs Venta Directa --}}
                            <div class="mb-2">
                                <label class="form-label text-white-50 small mb-1 fw-bold">🎯 Destino de la Bobina:</label>
                                <select name="roll_destination" id="rollDestinationSelect"
                                        class="form-select form-select-sm bg-dark text-white border-primary fw-bold"
                                        onchange="updateExtrusionPreview()">
                                    <option value="sealing" selected>✂️ Para Selladora (Pasa a corte y confección)</option>
                                    <option value="direct_sale">📦 Venta Directa / Despacho (Zanahoria, bobina final)</option>
                                </select>
                            </div>

                            {{-- Campo condicional para cm de fuelle --}}
                            <div class="mt-2 d-none" id="extrusionGussetCmWrapper">
                                <label class="form-label text-warning small mb-1 fw-bold">Medida de Fuelle (cm por lado):</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.5" min="1" max="50" name="extrusion_gusset_cm" id="extrusionGussetInput"
                                           class="form-control bg-dark text-warning border-warning fw-bold text-center"
                                           value="5" oninput="updateExtrusionPreview()">
                                    <span class="input-group-text bg-secondary text-white fw-bold">cm</span>
                                </div>
                            </div>
                        </div>

                        {{-- Columna 2: Cantidad de Bobinas & Peso en Báscula --}}
                        <div class="col-12 col-lg-4">
                            {{-- Cantidad de Bobinas --}}
                            <div class="mb-3">
                                <label class="form-label text-white fw-bold small">
                                    2. Cantidad de Bobinas en la Báscula:
                                </label>
                                <div class="input-group input-group-lg">
                                    <input type="number" step="1" min="1" max="50" name="quantity" id="quantityInput"
                                           class="form-control bg-dark text-white border-secondary text-center fw-bold fs-4"
                                           value="1" required oninput="recalcGrading()">
                                    <span class="input-group-text bg-secondary text-white fw-bold">Bobina(s)</span>
                                </div>
                                <div class="d-flex gap-1 mt-1">
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold active" id="btnQty1" onclick="setQty(1)">1 Bobina</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold" onclick="setQty(2)">2</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold" onclick="setQty(3)">3</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold" onclick="setQty(4)">4</button>
                                </div>
                            </div>

                            {{-- Peso Global de Báscula --}}
                            <div>
                                <label class="form-label text-white fw-bold small d-flex justify-content-between">
                                    <span>3. Peso de la Bobina en Báscula (Kg):</span>
                                    <span class="text-warning small">Pesar rollo completo</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-warning text-dark fw-bold">⚖️</span>
                                    <input type="number" step="0.01" min="0.01" max="2000" name="weight" id="weightInput"
                                           class="form-control bg-dark text-warning border-warning text-center fw-bold fs-3"
                                           placeholder="0.00" required oninput="recalcGrading()">
                                    <span class="input-group-text bg-secondary text-white fw-bold">Kg</span>
                                </div>
                            </div>

                            {{-- Purga / Merma de Arranque Simultánea Opcional --}}
                            <div class="mt-2">
                                <label class="form-label text-white-50 small d-flex justify-content-between mb-1">
                                    <span>⚠️ Purga / Merma de arranque en esta bobina (Kg) (Opcional):</span>
                                    <span class="text-warning small" style="font-size: 11px;">Material calibrado/desechado</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-dark border-secondary text-warning">⚠️</span>
                                    <input type="number" step="0.01" min="0" max="500" name="waste_weight_kg" id="wasteWeightInput"
                                           class="form-control bg-dark text-warning border-secondary text-center fw-bold"
                                           placeholder="0.00 (Opcional)">
                                    <span class="input-group-text bg-dark border-secondary text-white-50">Kg</span>
                                </div>
                            </div>
                        </div>
                @else
                    {{-- MODO SELLADORA / CONVERSIÓN: CATÁLOGO DE BOLSAS TERMINADAS --}}
                    <input type="hidden" name="source_bobina_id" id="sourceBobinaIdInput" value="{{ $mountedBobina?->id ?? '' }}">
                    <div class="row g-3">
                        {{-- Columna 1: Selección de Producto / Medida --}}
                        <div class="col-12 col-lg-5">
                            <label class="form-label text-white fw-bold small d-flex justify-content-between align-items-center mb-1">
                                <span>1. Medida / Producto:</span>
                                <span class="badge bg-secondary text-white" id="selectedProductBadge">Ninguna medida seleccionada</span>
                            </label>

                            {{-- Filtro Estricto de Compatibilidad con Bobina Montada --}}
                            @if(!empty($mountedBobina))
                                @php
                                    $mProduct = $mountedBobina->product;
                                    $mWidth = (float)($mProduct?->effective_width_cm ?? 0);
                                    $mMat = $mProduct?->effective_material_type ?? 'Baja Densidad';
                                    $mHasGusset = (bool)($mProduct?->effective_has_gusset ?? false);
                                @endphp
                                <div class="alert alert-info py-1 px-2 mb-2 d-flex justify-content-between align-items-center small shadow-sm" style="font-size: 11px; background: rgba(13, 202, 240, 0.1) !important; border-color: rgba(13, 202, 240, 0.3) !important;">
                                    <span>
                                        🔒 <strong>Filtro Industrial Estricto:</strong> Solo puedes sellar bolsas de <strong>{{ round($mWidth, 1) }} cm</strong>, <strong>{{ $mHasGusset ? 'Con Fuelle' : 'Plana' }}</strong> y <strong>{{ $mMat }}</strong>.
                                    </span>
                                    <span class="badge bg-info text-dark font-monospace">Bobina #{{ $mountedBobina->id }}</span>
                                </div>
                            @endif

                            {{-- Accesos Rápidos de Medidas Populares o Compatibles --}}
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @if(!empty($mountedBobina))
                                    <span class="text-info fw-bold small align-self-center me-1" style="font-size: 11px;">🎯 Compatibles:</span>
                                    @forelse($compatibleProducts->take(8) as $fastProd)
                                        <button type="button" class="btn btn-outline-info btn-sm text-truncate fast-prod-btn py-0 px-2 fw-bold"
                                                style="font-size: 11px; max-width: 180px; border-radius: 6px;"
                                                onclick="selectProductById({{ $fastProd->id }})">
                                            {{ $fastProd->name }}
                                        </button>
                                    @empty
                                        <span class="badge bg-danger text-white py-1">
                                            ⚠️ No hay bolsas compatibles en catálogo con esta medida/material
                                        </span>
                                    @endforelse
                                @else
                                    <span class="text-white-50 small align-self-center me-1" style="font-size: 10px;">Frecuentes:</span>
                                    @foreach($products->take(6) as $fastProd)
                                        <button type="button" class="btn btn-outline-secondary btn-sm text-truncate text-white fast-prod-btn py-0 px-2"
                                                style="font-size: 11px; max-width: 140px; border-radius: 6px;"
                                                onclick="selectProductById({{ $fastProd->id }})">
                                            {{ $fastProd->name }}
                                        </button>
                                    @endforeach
                                @endif
                            </div>

                            {{-- Buscador Inteligente Reactivo --}}
                            <div class="position-relative mb-2" id="smartProductSearchWrapper">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-dark border-secondary text-info">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text"
                                           id="productSearchInput"
                                           class="form-control bg-dark text-white border-secondary fw-bold"
                                           placeholder="🔍 Escribe medida o nombre (ej: 40x60, cristal, negra)..."
                                           autocomplete="off"
                                           autocorrect="off"
                                           spellcheck="false"
                                           onfocus="openProductDropdown()"
                                           oninput="handleSearchInput(this.value)"
                                           onkeydown="handleSearchKeydown(event)">
                                    <button type="button" 
                                            class="btn btn-outline-secondary d-none text-white" 
                                            id="clearSearchBtn" 
                                            onclick="clearProductSearch()" 
                                            title="Limpiar búsqueda">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>

                                {{-- Dropdown Flotante Reactivo con Resultados --}}
                                <div id="productDropdownList"
                                     class="position-absolute w-100 bg-dark border border-info rounded-3 shadow-lg d-none mt-1 p-0"
                                     style="z-index: 1050; max-height: 380px; overflow-y: auto; box-shadow: 0 12px 30px rgba(0,0,0,0.85) !important;">
                                </div>
                            </div>

                            {{-- Input Hidden para envío del Formulario --}}
                            <input type="hidden" name="product_id" id="selectedProductId" value="" required>

                            {{-- Select Oculto de Respaldo para Compatibilidad --}}
                            <select id="productSelect" style="display: none;" tabindex="-1" aria-hidden="true">
                                <option value="">-- Seleccionar Medida o Producto --</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}"
                                            data-theoretical="{{ (float)($p->unit_weight_kg > 0 ? $p->unit_weight_kg : 1.0) }}"
                                            data-target="{{ (int)($p->target_units_per_shift ?: 5) }}"
                                            data-millar="{{ (float)($p->millar_per_bulto ?: 1) }}"
                                            data-variable="{{ $p->is_variable_quantity ? '1' : '0' }}">
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Ficha Informativa del Producto Seleccionado --}}
                            <div class="p-2 rounded bg-dark border border-secondary text-white-50 small mt-2" id="productSpecsBox" style="font-size: 11px;">
                                <div class="d-flex justify-content-between">
                                    <span>Peso Teórico por Millar:</span>
                                    <strong class="text-white" id="specTheoreticalWeight">-- Kg</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Meta por Turno:</span>
                                    <strong class="text-white" id="specShiftTarget">-- millares</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Pago estimado por millar:</span>
                                    <strong class="text-success font-monospace" id="specTariffPerUnit">-- USD</strong>
                                </div>
                            </div>
                        </div>

                        {{-- Columna 2: Cantidad (Multi-millar) & Peso en Báscula --}}
                        <div class="col-12 col-lg-4">
                            {{-- Cantidad de Millares --}}
                            <div class="mb-3">
                                <label class="form-label text-white fw-bold small">
                                    2. Cantidad de Millares en la Báscula:
                                </label>
                                <div class="input-group input-group-lg">
                                    <input type="number" step="1" min="1" max="500" name="quantity" id="quantityInput"
                                           class="form-control bg-dark text-white border-secondary text-center fw-bold fs-4"
                                           value="10" required oninput="recalcGrading()">
                                    <span class="input-group-text bg-secondary text-white fw-bold">Millares</span>
                                </div>
                                {{-- Botones Rápidos de Lotes --}}
                                <div class="d-flex gap-1 mt-1">
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold" onclick="setQty(1)">1</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold" onclick="setQty(5)">5</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold active" id="btnQty10" onclick="setQty(10)">10</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold" onclick="setQty(15)">15</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold" onclick="setQty(20)">20</button>
                                    <button type="button" class="btn btn-outline-info btn-sm flex-fill fw-bold" onclick="setQty(25)">25</button>
                                </div>
                            </div>

                            {{-- Peso Global de Báscula --}}
                            <div>
                                <label class="form-label text-white fw-bold small d-flex justify-content-between">
                                    <span>3. Peso Total en Báscula (Kg):</span>
                                    <span class="text-warning small">Pesar fardo completo</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-warning text-dark fw-bold">⚖️</span>
                                    <input type="number" step="0.01" min="0.01" max="2000" name="weight" id="weightInput"
                                           class="form-control bg-dark text-warning border-warning text-center fw-bold fs-3"
                                           placeholder="0.00" required oninput="recalcGrading()">
                                    <span class="input-group-text bg-secondary text-white fw-bold">Kg</span>
                                </div>
                            </div>
                        </div>
                @endif

                    {{-- Columna 3: Monitor de Calidad (Grado A, B, C) y Acción --}}
                    <div class="col-12 col-lg-3 d-flex flex-column justify-content-between">
                        {{-- Card de Calidad en Vivo --}}
                        <div class="card border-0 p-3 text-center mb-2" id="gradingCard"
                             style="background: #0f172a; border-radius: 12px; border: 2px solid #334155 !important;">
                            <span class="text-uppercase fw-bold text-white-50" style="font-size: 10px;">EVALUACIÓN DE CALIDAD</span>
                            <div class="h4 fw-bold mb-0 mt-1" id="gradingBadge">
                                <span class="badge bg-secondary text-white">COLOCA EL PESO</span>
                            </div>
                            <div class="text-white-50 small mt-1 font-monospace" id="gradingDetails">
                                Promedio: 0.00 Kg/millar
                            </div>
                            <div class="mt-2 pt-2 border-top border-secondary small text-info fw-bold font-monospace" id="estimatedEarnBox">
                                Suma a tu nómina: + $0.00 USD
                            </div>
                        </div>

                        {{-- Botones de Envío --}}
                        <div class="d-flex flex-column gap-2">
                            <button type="button" class="btn btn-success btn-lg fw-bold py-2 d-flex align-items-center justify-content-center gap-2 shadow"
                                    onclick="submitBatch('direct_print')">
                                <span>🖨️</span> GUARDAR E IMPRIMIR
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm fw-bold py-2"
                                    onclick="submitBatch('save_only')">
                                <span>💾</span> Solo Guardar Pesaje
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- HISTORIAL DE PESAJES DEL TURNO --}}
        <div class="card border-0 shadow-sm" style="background: #1e293b; border-radius: 14px; border: 1px solid #334155 !important;">
            <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                    <span>📋</span> Pesajes Registrados en este Turno ({{ $shiftProductions->count() }})
                </h5>
                @if($shiftProductions->isNotEmpty())
                    <a href="{{ route('ticket.shift', ['shift_id' => $activeShift->id]) }}" class="btn btn-outline-info btn-sm fw-bold">
                        🖨️ Imprimir Todas las Etiquetas del Turno
                    </a>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="text-white-50 small text-uppercase" style="background: #0f172a;">
                        <tr>
                            <th class="ps-3">Hora</th>
                            <th>Medida / Producto</th>
                            <th>Lote</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-center">Peso Total</th>
                            <th class="text-center">Promedio</th>
                            <th class="text-center">Calidad</th>
                            <th class="text-center">Ganancia USD</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shiftProductions as $prod)
                            @if($prod->is_waste_record)
                                @php
                                    $wType = $prod->metadata['waste_type'] ?? 'purga_calibracion_extrusion';
                                    $wNotes = $prod->metadata['waste_notes'] ?? '';
                                    $srcBatch = $prod->metadata['source_bobina_batch'] ?? null;
                                    $srcOp = $prod->metadata['source_bobina_operator'] ?? null;
                                    $srcProd = $prod->metadata['source_bobina_product'] ?? null;
                                @endphp
                                <tr class="table-active bg-warning bg-opacity-10">
                                    <td class="ps-3 font-monospace text-white-50">
                                        {{ $prod->recorded_at ? $prod->recorded_at->format('h:i A') : '--' }}
                                    </td>
                                    <td>
                                        @if($wType === 'defecto_extrusion')
                                            <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                                <span class="badge bg-danger text-white fw-bold">💥 DEFECTO EXTRUSIÓN</span>
                                                @if($srcBatch)
                                                    <span class="badge bg-dark border border-danger text-danger font-monospace" style="font-size: 11px;">
                                                        Bobina Lote: {{ $srcBatch }}
                                                    </span>
                                                @endif
                                                @if($srcOp)
                                                    <span class="badge bg-dark border border-secondary text-white-50" style="font-size: 10px;">
                                                        Extrusor: {{ $srcOp }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-white small d-block">
                                                {{ $wNotes ? 'Falla reportada: ' . $wNotes : 'Material no conforme descartado en sellado' }}
                                            </span>
                                            <span class="text-white-50 small d-block" style="font-size: 10px;">
                                                ℹ️ Descontado del saldo de la bobina montada (protección de nómina al sellador).
                                            </span>
                                        @elseif($wType === 'merma_operativa_sellado')
                                            <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                                <span class="badge bg-warning text-dark fw-bold">✂️ MERMA SELLADORA</span>
                                                <span class="badge bg-dark border border-secondary text-white-50" style="font-size: 10px;">
                                                    Ajuste de máquina
                                                </span>
                                            </div>
                                            <span class="text-white small d-block">
                                                {{ $wNotes ? $wNotes : 'Calibración de cuchilla, temperatura o troquel' }}
                                            </span>
                                        @else
                                            <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                                <span class="badge bg-warning text-dark fw-bold">⚠️ PURGA EXTRUSIÓN</span>
                                                <span class="badge bg-dark border border-secondary text-white-50" style="font-size: 10px;">
                                                    Boquilla / Calibre
                                                </span>
                                            </div>
                                            <span class="text-white-50 small d-block">
                                                {{ $prod->formula ? 'Fórmula: ' . $prod->formula->name : 'Purga de arranque de máquina extrusora' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-dark font-monospace {{ $wType === 'defecto_extrusion' ? 'text-danger border border-danger' : 'text-warning border border-warning' }}">
                                            {{ $wType === 'defecto_extrusion' ? 'DEF-EXT' : ($wType === 'merma_operativa_sellado' ? 'MERMA-SEL' : 'MERMA-PURGA') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary text-white px-2 py-1">Registrado</span>
                                    </td>
                                    <td class="text-center text-white-50">--</td>
                                    <td class="text-center font-monospace {{ $wType === 'defecto_extrusion' ? 'text-danger' : 'text-warning' }} fw-bold">
                                        {{ number_format($prod->waste_weight_kg, 2) }} Kg
                                    </td>
                                    <td class="text-center text-white-50">--</td>
                                    <td class="text-center">
                                        @if($wType === 'defecto_extrusion')
                                            <span class="badge bg-danger text-white fw-bold">CARGO A EXTRUSIÓN</span>
                                        @elseif($wType === 'merma_operativa_sellado')
                                            <span class="badge bg-warning text-dark fw-bold">AJUSTE SELLADORA</span>
                                        @else
                                            <span class="badge bg-warning text-dark fw-bold">MERMA / PURGA</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-white-50 font-monospace">$0.00</td>
                                    <td class="text-end pe-3">
                                        @if(!$prod->is_printed || Auth::user()->role === 'admin')
                                            <form action="{{ route('operator.destroy_batch', $prod->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar este registro de merma de {{ number_format($prod->waste_weight_kg, 2) }} Kg?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar merma">
                                                    🗑️
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td class="ps-3 font-monospace text-white-50">
                                        {{ $prod->recorded_at ? $prod->recorded_at->format('h:i A') : '--' }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                            <strong class="text-white">{{ $prod->product->name ?? 'Bolsa' }}</strong>
                                            @if($prod->product)
                                                @php
                                                    $pMat = $prod->product->effective_material_type;
                                                    $pIsAlta = stripos($pMat, 'ALTA') !== false;
                                                    $pHasGusset = (bool)$prod->product->effective_has_gusset;
                                                    $pGussetVal = (float)($prod->product->gusset_cm ?? 0);
                                                    $pWidth = (float)$prod->product->effective_width_cm;
                                                @endphp
                                                <span class="badge {{ $pIsAlta ? 'bg-primary' : 'bg-info text-dark' }} fw-bold" style="font-size: 10px;">
                                                    {{ $pIsAlta ? '🔷 ALTA' : '🔹 BAJA' }}
                                                </span>
                                                <span class="badge {{ $pHasGusset ? 'bg-warning text-dark' : 'bg-secondary' }} fw-bold" style="font-size: 10px;">
                                                    {{ $pHasGusset ? '📐 FUELLE' . ($pGussetVal > 0 ? ' ' . round($pGussetVal, 1) . 'cm' : '') : '⚪ PLANA' }}
                                                </span>
                                                @if($pWidth > 0)
                                                    <span class="badge bg-dark border border-secondary text-white fw-bold" style="font-size: 10px;">
                                                        📏 {{ round($pWidth, 1) }} cm
                                                    </span>
                                                @endif
                                            @endif
                                            @if((float)$prod->waste_weight_kg > 0)
                                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning fw-bold" style="font-size: 10px;" title="Purga de calibración descartada en este pesaje">
                                                    ⚠️ Purga: {{ number_format($prod->waste_weight_kg, 2) }} Kg
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-white-50 small font-monospace">{{ $prod->product->sku ?? '' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark font-monospace text-warning border border-secondary">
                                            {{ $prod->effective_batch_code }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($prod->is_printed)
                                            <span class="badge bg-success bg-opacity-25 text-success border border-success fw-normal px-2 py-1"
                                                  title="Etiqueta física emitida: Registro sellado">
                                                ✓ Impreso
                                            </span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-25 text-warning border border-warning fw-normal px-2 py-1"
                                                  title="Sin imprimir: Editable por operario">
                                                ⏳ Pre-cargado
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold text-white">
                                        {{ (int)$prod->quantity }}
                                    </td>
                                    <td class="text-center font-monospace">
                                        {{ number_format($prod->weight, 2) }} Kg
                                    </td>
                                    <td class="text-center font-monospace text-info">
                                        {{ $prod->quantity > 0 ? number_format($prod->weight / $prod->quantity, 3) : '--' }} Kg
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $isBob = ($prod->product && $prod->product->is_variable_quantity) || 
                                                     ($prod->product && in_array(strtoupper($prod->product->sale_unit ?? ''), ['BOBINA', 'KG', 'ROLLO'])) ||
                                                     isset($prod->metadata['roll_destination']);
                                            $grade = strtoupper($prod->weight_quality_grade ?: 'B');
                                        @endphp
                                        @if($isBob)
                                            <span class="badge bg-info text-dark fw-bold">🌀 EN PROCESO</span>
                                        @elseif($grade === 'A')
                                            <span class="badge bg-warning text-dark fw-bold">🟡 GRADO A (Sobrepeso)</span>
                                        @elseif($grade === 'C')
                                            <span class="badge bg-danger text-white fw-bold">🔴 GRADO C (Subcalibre)</span>
                                        @else
                                            <span class="badge bg-success text-white fw-bold">🟢 GRADO B (Óptimo)</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-monospace fw-bold text-success">
                                        +${{ number_format($prod->labor_earned_amount, 2) }}
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end align-items-center gap-1">
                                            {{-- Botón Imprimir / Etiquetas --}}
                                            <a href="{{ route('ticket', ['id' => $prod->id, 'scope' => 'bulto']) }}"
                                               class="btn {{ $prod->is_printed ? 'btn-outline-warning' : 'btn-success' }} btn-sm fw-bold"
                                               title="{{ $prod->is_printed ? 'Reimprimir etiqueta' : 'Imprimir y sellar etiqueta' }}">
                                                🖨️ {{ $prod->is_printed ? 'Etiquetas' : 'Imprimir' }}
                                            </a>

                                            @if(!$prod->is_printed || Auth::user()->role === 'admin')
                                                {{-- Botón Editar (Solo precargado o admin) --}}
                                                <button type="button" 
                                                        class="btn btn-outline-info btn-sm"
                                                        title="Editar pesaje pre-cargado"
                                                        onclick="openEditBatchModal(this)"
                                                        data-id="{{ $prod->id }}"
                                                        data-product-name="{{ $prod->product->name ?? 'Bolsa' }}"
                                                        data-quantity="{{ (float)$prod->quantity }}"
                                                        data-weight="{{ (float)$prod->weight }}">
                                                    ✏️
                                                </button>

                                                {{-- Botón Eliminar (Solo precargado o admin) --}}
                                                <form action="{{ route('operator.destroy_batch', $prod->id) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este pesaje pre-cargado de {{ (int)$prod->quantity }} millares?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar pesaje pre-cargado">
                                                        🗑️
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Candado de Bloqueo Industrial --}}
                                                <span class="badge bg-secondary bg-opacity-50 text-white-50 px-2 py-1 border border-secondary" 
                                                      style="font-size: 11px; cursor: help;"
                                                      title="Sellado con etiqueta física. Si hubo un error, consulte al Jefe de Operaciones.">
                                                    🔒 Sellado
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-white-50">
                                    Aún no has registrado pesajes en este turno. ¡Comienza arriba registrando tu primera pesada!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

{{-- ESTILOS PARA EL BUSCADOR INTELIGENTE --}}
<style>
    .product-search-item {
        cursor: pointer;
        transition: background-color 0.15s ease, border-left-color 0.15s ease;
        border-left: 3px solid transparent;
    }
    .product-search-item:hover, .product-search-item.active {
        background-color: #1e3a8a !important;
        border-left: 3px solid #38bdf8 !important;
    }
    .product-search-item mark {
        background-color: #facc15 !important;
        color: #0f172a !important;
        font-weight: 700;
        padding: 0 3px;
        border-radius: 2px;
    }
    #productDropdownList::-webkit-scrollbar {
        width: 8px;
    }
    #productDropdownList::-webkit-scrollbar-track {
        background: #0f172a;
    }
    #productDropdownList::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 4px;
    }
    #productDropdownList::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }
</style>

@php
    if (!isset($productsCatalogJson)) {
        $targetSalary = (float)($targetUser->daily_salary ?: 15.0);
        $catalogItems = [];
        foreach ($products as $p) {
            $t = (int)($p->target_units_per_shift ?: 5);
            $tf = $t > 0 ? ($targetSalary / $t) : 0.0;
            $catalogItems[] = [
                'id'          => $p->id,
                'name'        => $p->name,
                'sku'         => $p->sku ?? '',
                'sale_unit'   => $p->sale_unit ?? '',
                'theoretical' => (float)($p->unit_weight_kg > 0 ? $p->unit_weight_kg : 1.0),
                'target'      => $t,
                'millar'      => (float)($p->millar_per_bulto ?: 1),
                'is_variable' => (bool)$p->is_variable_quantity,
                'width_cm'    => (float)$p->effective_width_cm,
                'gusset_cm'   => (float)($p->gusset_cm ?? 0),
                'has_gusset'  => (bool)$p->effective_has_gusset,
                'caliber'     => (float)($p->gauge_caliber ?? 0),
                'material'    => $p->effective_material_type,
                'tariff'      => round($tf, 2),
            ];
        }
        $productsCatalogJson = json_encode($catalogItems);
    }

    $mountedBobinaSpec = null;
    if (!empty($mountedBobina)) {
        $mProd = $mountedBobina->product;
        $mountedBobinaSpec = [
            'id'         => $mountedBobina->id,
            'code'       => $mountedBobina->effective_batch_code,
            'name'       => $mProd?->name ?? 'Bobina Madre',
            'width_cm'   => (float)($mProd?->effective_width_cm ?? 0),
            'material'   => $mProd?->effective_material_type ?? 'Baja Densidad',
            'has_gusset' => (bool)($mProd?->effective_has_gusset ?? false),
            'gusset_cm'  => (float)($mProd?->gusset_cm ?? 0),
        ];
    }
@endphp

{{-- SCRIPT REACTIVO DE CÁLCULO Y BUSCADOR INTELIGENTE (JS VANILLA) --}}
<script>
    const dailySalary = {{ (float)($targetUser->daily_salary ?: 15.0) }};

    // Catálogo completo de productos para filtrado reactivo instantáneo (sub-milisegundo)
    const productsCatalog = {!! $productsCatalogJson !!};
    const mountedBobinaSpec = {!! json_encode($mountedBobinaSpec) !!};

    // Precomputar cadena de búsqueda normalizada (sin acentos, minúsculas)
    function normalizeStr(str) {
        return (str || '')
            .toString()
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    productsCatalog.forEach(p => {
        p._searchStr = normalizeStr(`${p.name} ${p.sku} ${p.sale_unit} ${p.material || ''}`);
    });

    let currentFiltered = [];
    let highlightedIndex = -1;
    let activeProduct = null;

    // Validación estricta de compatibilidad en Frontend
    function checkProductCompatibilityWithMountedBobina(p) {
        if (!mountedBobinaSpec) return { compatible: true, error: null };

        // 0. Una bobina intermedia no puede seleccionarse como bolsa terminada para sellar
        if (p.is_variable || (p.sale_unit && p.sale_unit.toUpperCase() === 'BOBINA') || (p.name && p.name.toUpperCase().includes('BOBINA MANGA'))) {
            return {
                compatible: false,
                error: 'Este registro es una bobina intermedia de extrusión, no una bolsa terminada para sellar'
            };
        }

        // 1. Validar Ancho (tolerancia de 0.5 cm)
        const bobWidth = parseFloat(mountedBobinaSpec.width_cm || 0);
        const bagWidth = parseFloat(p.width_cm || 0);
        if (bobWidth > 0 && bagWidth > 0 && Math.abs(bobWidth - bagWidth) > 0.5) {
            return {
                compatible: false,
                error: `El ancho de la bolsa (${bagWidth} cm) no coincide con la bobina (${bobWidth} cm)`
            };
        }

        // 2. Validar Formato (Fuelle vs Plana)
        const bobHasGusset = Boolean(mountedBobinaSpec.has_gusset);
        const bagHasGusset = Boolean(p.has_gusset);
        if (bobHasGusset !== bagHasGusset) {
            return {
                compatible: false,
                error: bobHasGusset 
                    ? 'La bobina es Con Fuelle pero esta bolsa es Plana (sin fuelle)' 
                    : 'La bobina es Plana pero esta bolsa tiene Fuelle'
            };
        }

        // 3. Validar Material (Alta Densidad vs Baja Densidad)
        const bobMat = (mountedBobinaSpec.material || '').toUpperCase();
        const bagMat = (p.material || '').toUpperCase();
        if (bobMat && bagMat) {
            const isBobAlta = bobMat.includes('ALTA');
            const isBagAlta = bagMat.includes('ALTA');
            const isBobBaja = bobMat.includes('BAJA');
            const isBagBaja = bagMat.includes('BAJA');

            if ((isBobAlta && isBagBaja) || (isBobBaja && isBagAlta)) {
                return {
                    compatible: false,
                    error: `La bobina es de ${mountedBobinaSpec.material} pero esta bolsa es de ${p.material}`
                };
            }
        }

        return { compatible: true, error: null };
    }

    function getAvailableProductsPool() {
        if (!mountedBobinaSpec) {
            return productsCatalog;
        }
        return productsCatalog.filter(p => checkProductCompatibilityWithMountedBobina(p).compatible);
    }

    function setQty(val) {
        document.getElementById('quantityInput').value = val;
        recalcGrading();
    }

    // Compatibilidad con selectProduct previo
    function selectProduct(id) {
        selectProductById(id, true);
    }

    function selectProductById(id, isUserAction = true) {
        const prod = productsCatalog.find(p => p.id == id);
        if (!prod) return;

        // Validar compatibilidad industrial estricta
        const compat = checkProductCompatibilityWithMountedBobina(prod);
        if (!compat.compatible) {
            if (isUserAction) {
                alert(`⛔ BLOQUEO DE INCOMPATIBILIDAD INDUSTRIAL:\n\n${compat.error}.\n\nNo puedes sellar este producto con la bobina montada actual (Lote: ${mountedBobinaSpec ? mountedBobinaSpec.code : ''}).\nSi necesitas sellar este producto, debes desmontar primero la bobina montada.`);
            }
            return;
        }

        activeProduct = prod;
        document.getElementById('selectedProductId').value = prod.id;
        
        // Sincronizar select oculto si existe
        const sel = document.getElementById('productSelect');
        if (sel) sel.value = prod.id;

        // Actualizar caja de texto del buscador
        const searchInput = document.getElementById('productSearchInput');
        if (searchInput) {
            searchInput.value = prod.name;
        }
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) {
            clearBtn.classList.remove('d-none');
        }

        // Actualizar Badge de Estado con detalles técnicos
        const badge = document.getElementById('selectedProductBadge');
        if (badge) {
            const matLabel = prod.material ? prod.material : 'Estándar';
            const fmtLabel = prod.has_gusset ? 'Fuelle' : 'Plana';
            badge.className = 'badge bg-success text-white';
            badge.innerHTML = `✓ ${prod.name} <span class="badge bg-dark ms-1">${matLabel} - ${fmtLabel}</span>`;
        }

        // Actualizar Ficha de Especificaciones
        const specWeight = document.getElementById('specTheoreticalWeight');
        if (specWeight) specWeight.textContent = prod.theoretical.toFixed(3) + ' Kg';

        const specTarget = document.getElementById('specShiftTarget');
        if (specTarget) specTarget.textContent = prod.target + ' millares';

        const specTariff = document.getElementById('specTariffPerUnit');
        if (specTariff) specTariff.textContent = '$' + prod.tariff.toFixed(2) + ' USD / millar';

        // Cerrar dropdown
        closeProductDropdown();

        // Recalcular monitor de calidad
        recalcGrading();

        // Auto-enfoque al campo de Peso en Báscula para agilidad del operario
        const weightInput = document.getElementById('weightInput');
        if (weightInput) {
            weightInput.focus();
            weightInput.select();
        }
    }

    function clearProductSearch() {
        const searchInput = document.getElementById('productSearchInput');
        if (searchInput) searchInput.value = '';

        document.getElementById('selectedProductId').value = '';
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) clearBtn.classList.add('d-none');

        activeProduct = null;

        const sel = document.getElementById('productSelect');
        if (sel) sel.value = '';

        const badge = document.getElementById('selectedProductBadge');
        if (badge) {
            badge.className = 'badge bg-secondary text-white';
            badge.textContent = 'Ninguna medida seleccionada';
        }

        const specWeight = document.getElementById('specTheoreticalWeight');
        if (specWeight) specWeight.textContent = '-- Kg';

        const specTarget = document.getElementById('specShiftTarget');
        if (specTarget) specTarget.textContent = '-- millares';

        const specTariff = document.getElementById('specTariffPerUnit');
        if (specTariff) specTariff.textContent = '-- USD';

        recalcGrading();
        renderDropdownList('', true);
        if (searchInput) searchInput.focus();
    }

    function openProductDropdown() {
        const input = document.getElementById('productSearchInput');
        if (!input) return;
        renderDropdownList(input.value, true);
        const dropdown = document.getElementById('productDropdownList');
        if (dropdown) dropdown.classList.remove('d-none');
    }

    function closeProductDropdown() {
        const dropdown = document.getElementById('productDropdownList');
        if (dropdown) dropdown.classList.add('d-none');
        highlightedIndex = -1;
    }

    function handleSearchInput(value) {
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) {
            if (value.trim()) {
                clearBtn.classList.remove('d-none');
            } else {
                clearBtn.classList.add('d-none');
            }
        }
        renderDropdownList(value, true);
        const dropdown = document.getElementById('productDropdownList');
        if (dropdown) dropdown.classList.remove('d-none');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function highlightText(text, tokens) {
        if (!tokens.length) return escapeHtml(text);
        const safeText = escapeHtml(text);
        let regexParts = tokens.map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).filter(Boolean);
        if (!regexParts.length) return safeText;
        const regex = new RegExp(`(${regexParts.join('|')})`, 'gi');
        return safeText.replace(regex, '<mark>$1</mark>');
    }

    function renderDropdownList(query, resetHighlight = false) {
        const dropdown = document.getElementById('productDropdownList');
        if (!dropdown) return;

        const pool = getAvailableProductsPool();
        const qNorm = normalizeStr(query.trim());
        const tokens = qNorm.split(/\s+/).filter(Boolean);

        if (!tokens.length) {
            currentFiltered = pool.slice(0, 30);
        } else {
            currentFiltered = pool.filter(p => tokens.every(tok => p._searchStr.includes(tok)));
        }

        if (resetHighlight) {
            highlightedIndex = currentFiltered.length > 0 ? 0 : -1;
        }

        if (currentFiltered.length === 0) {
            if (mountedBobinaSpec && pool.length === 0) {
                dropdown.innerHTML = `
                    <div class="p-3 text-center text-warning">
                        <div class="fs-4 mb-1">⚠️</div>
                        <div class="fw-bold">No hay bolsas compatibles en el catálogo</div>
                        <small class="d-block text-white-50 mt-1">
                            Bobina montada: ${mountedBobinaSpec.width_cm} cm | ${mountedBobinaSpec.has_gusset ? 'Con Fuelle' : 'Plana'} | ${mountedBobinaSpec.material}.
                        </small>
                        <small class="text-info d-block mt-2">
                            Desmonta la bobina si vas a sellar otra medida o registra la bolsa compatible en el catálogo.
                        </small>
                    </div>
                `;
            } else {
                dropdown.innerHTML = `
                    <div class="p-3 text-center text-white-50">
                        <div class="fs-4 mb-1">🔍</div>
                        <div class="fw-bold">No se encontraron productos coincidentes</div>
                        <small>Prueba buscando con otra medida o nombre</small>
                    </div>
                `;
            }
            return;
        }

        let html = '';
        if (mountedBobinaSpec) {
            html += `
                <div class="px-3 py-1 bg-info bg-opacity-25 text-info small border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <span>🔒 Solo compatibles (${pool.length}): <strong>${mountedBobinaSpec.width_cm}cm • ${mountedBobinaSpec.has_gusset ? 'Con Fuelle' : 'Plana'} • ${mountedBobinaSpec.material}</strong></span>
                    <span class="badge bg-dark text-info">Bobina #${mountedBobinaSpec.id}</span>
                </div>
            `;
        } else if (!tokens.length) {
            html += `
                <div class="px-3 py-1 bg-secondary bg-opacity-25 text-white-50 small border-bottom border-secondary border-opacity-25 d-flex justify-content-between">
                    <span>⚡ Catálogo rápido (${productsCatalog.length} productos)</span>
                    <span>Usa flechas ↑↓ y Enter</span>
                </div>
            `;
        } else {
            html += `
                <div class="px-3 py-1 bg-info bg-opacity-10 text-info small border-bottom border-secondary border-opacity-25 d-flex justify-content-between">
                    <span>Encontrados: <strong>${currentFiltered.length}</strong> productos</span>
                    <span>Presiona [Enter] para elegir</span>
                </div>
            `;
        }

        currentFiltered.forEach((p, idx) => {
            const isSelected = activeProduct && activeProduct.id === p.id;
            const isHighlighted = idx === highlightedIndex;
            const highlightedName = highlightText(p.name, tokens);
            const highlightedSku = highlightText(p.sku || '', tokens);

            const isAlta = (p.material && p.material.toUpperCase().includes('ALTA'));
            const matBadgeClass = isAlta ? 'bg-primary' : 'bg-info text-dark';
            const matBadgeText = p.material || 'Estándar';
            const gussetBadge = p.has_gusset 
                ? '<span class="badge bg-warning text-dark fw-bold" style="font-size: 10px;">📐 FUELLE</span>'
                : '<span class="badge bg-secondary text-white" style="font-size: 10px;">⚪ PLANA</span>';

            html += `
                <div class="product-search-item px-3 py-2 border-bottom border-secondary border-opacity-25 text-white d-flex justify-content-between align-items-center ${isHighlighted ? 'active' : ''} ${isSelected ? 'bg-primary bg-opacity-25' : ''}"
                     id="searchItem-${idx}"
                     onmouseenter="setHighlightedIndex(${idx})"
                     onclick="selectProductById(${p.id})">
                    <div class="me-2 text-truncate">
                        <div class="fw-bold text-white fs-6 text-truncate d-flex align-items-center gap-1">
                            <span>${highlightedName}</span>
                        </div>
                        <div class="text-white-50 small d-flex flex-wrap gap-2 align-items-center mt-1" style="font-size: 11px;">
                            <span class="badge ${matBadgeClass} fw-bold" style="font-size: 10px;">${matBadgeText}</span>
                            ${gussetBadge}
                            ${p.width_cm > 0 ? `<span class="badge bg-dark border border-secondary text-white-50" style="font-size: 10px;">📏 ${p.width_cm} cm</span>` : ''}
                            ${p.sku ? `<span class="badge bg-secondary font-monospace">${highlightedSku}</span>` : ''}
                            <span>⚖️ Teórico: <strong class="text-info">${p.theoretical.toFixed(3)} Kg</strong>/millar</span>
                            <span>💰 Tarifa: <strong class="text-success">$${p.tariff.toFixed(2)} USD</strong></span>
                        </div>
                    </div>
                    <div class="text-end ps-2 flex-shrink-0">
                        <span class="badge bg-dark border border-secondary text-info fw-bold" title="Meta esperada por turno de trabajo">
                            Meta: ${p.target} mill/turno
                        </span>
                        ${isSelected ? '<span class="badge bg-success d-block mt-1">Activo</span>' : ''}
                    </div>
                </div>
            `;
        });

        dropdown.innerHTML = html;
        scrollHighlightedIntoView();
    }

    function setHighlightedIndex(index) {
        highlightedIndex = index;
        updateHighlightVisuals();
    }

    function updateHighlightVisuals() {
        const items = document.querySelectorAll('.product-search-item');
        items.forEach((item, idx) => {
            if (idx === highlightedIndex) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    function scrollHighlightedIntoView() {
        const item = document.getElementById(`searchItem-${highlightedIndex}`);
        if (item) {
            item.scrollIntoView({ block: 'nearest' });
        }
    }

    function handleSearchKeydown(e) {
        const dropdown = document.getElementById('productDropdownList');
        if (!dropdown) return;
        const isOpen = !dropdown.classList.contains('d-none');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!isOpen) {
                openProductDropdown();
                return;
            }
            if (currentFiltered.length > 0) {
                highlightedIndex = (highlightedIndex + 1) % currentFiltered.length;
                updateHighlightVisuals();
                scrollHighlightedIntoView();
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!isOpen) {
                openProductDropdown();
                return;
            }
            if (currentFiltered.length > 0) {
                highlightedIndex = (highlightedIndex - 1 + currentFiltered.length) % currentFiltered.length;
                updateHighlightVisuals();
                scrollHighlightedIntoView();
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (isOpen && highlightedIndex >= 0 && highlightedIndex < currentFiltered.length) {
                selectProductById(currentFiltered[highlightedIndex].id);
            } else if (isOpen && currentFiltered.length > 0) {
                selectProductById(currentFiltered[0].id);
            }
        } else if (e.key === 'Escape') {
            closeProductDropdown();
        }
    }

    // Cerrar el buscador flotante si se hace clic fuera del buscador
    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('smartProductSearchWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            closeProductDropdown();
        }
    });

    // Filtro inteligente por ancho de bobina montada (ej: 30 cm)
    function filterProductsByBobinaWidth(width) {
        if (!width || width <= 0) return;
        const input = document.getElementById('productSearchInput');
        if (input) {
            input.value = width.toString();
            handleSearchInput(input.value);
            openProductDropdown();
            input.focus();
        }
    }

    const isExtrusionMachineMode = {{ !empty($isExtrusionMachine) ? 'true' : 'false' }};

    function setExtrusionWidth(val) {
        const input = document.getElementById('extrusionWidthInput');
        if (input) {
            input.value = val;
            updateExtrusionPreview();
        }
        document.querySelectorAll('.fast-width-btn').forEach(btn => {
            btn.classList.toggle('active', btn.textContent.trim() === `${val} cm`);
        });
    }

    function toggleExtrusionGusset(val) {
        const wrapper = document.getElementById('extrusionGussetCmWrapper');
        if (wrapper) {
            if (val === '1') {
                wrapper.classList.remove('d-none');
            } else {
                wrapper.classList.add('d-none');
            }
        }
        updateExtrusionPreview();
    }

    function updateExtrusionPreview() {
        if (!isExtrusionMachineMode) return;
        const width = parseFloat(document.getElementById('extrusionWidthInput')?.value) || 22;
        const caliber = parseFloat(document.getElementById('extrusionCaliberInput')?.value) || 3.0;
        const hasGusset = document.getElementById('extrusionGussetSelect')?.value === '1';
        const gusset = hasGusset ? (parseFloat(document.getElementById('extrusionGussetInput')?.value) || 0) : 0;
        const mat = document.getElementById('extrusionMaterialSelect')?.value || 'Baja Densidad';

        const badge = document.getElementById('extrusionSpecBadge');
        if (badge) {
            badge.textContent = `Manga ${width} cm ${hasGusset ? ('Fuelle ' + gusset + ' cm') : 'Plana'} (${mat} C-${caliber})`;
        }
        recalcGrading();
    }

    function recalcGrading() {
        const qty = parseFloat(document.getElementById('quantityInput')?.value) || 0;
        const weight = parseFloat(document.getElementById('weightInput')?.value) || 0;

        const badge = document.getElementById('gradingBadge');
        const details = document.getElementById('gradingDetails');
        const card = document.getElementById('gradingCard');
        const earnBox = document.getElementById('estimatedEarnBox');

        if (!badge || !details || !card || !earnBox) return;

        if (isExtrusionMachineMode) {
            if (weight <= 0) {
                badge.innerHTML = '<span class="badge bg-secondary text-white">COLOCA EL PESO DE LA BOBINA</span>';
                details.textContent = 'Bobina en báscula: 0.00 Kg';
                earnBox.textContent = 'Bobinado en curso';
                card.style.borderColor = '#334155';
                return;
            }

            const width = parseFloat(document.getElementById('extrusionWidthInput')?.value) || 22;
            const hasGusset = document.getElementById('extrusionGussetSelect')?.value === '1';
            const gusset = hasGusset ? (parseFloat(document.getElementById('extrusionGussetInput')?.value) || 0) : 0;
            const mat = document.getElementById('extrusionMaterialSelect')?.value || 'Baja Densidad';
            const avgWeight = qty > 0 ? (weight / qty) : weight;

            const isAlta = mat.toUpperCase().includes('ALTA');
            const matBadge = `<span class="badge ${isAlta ? 'bg-primary' : 'bg-info text-dark'} fw-bold" style="font-size: 10px;">${isAlta ? '🔷 ALTA' : '🔹 BAJA'}</span>`;
            badge.innerHTML = `<span class="badge bg-info text-dark fw-bold fs-6">🌀 BOBINA DE EXTRUSIÓN</span> ${matBadge}`;
            details.innerHTML = `Manga <strong>${mat}</strong>: <strong>${width} cm</strong> ${hasGusset ? ('(Fuelle: ' + gusset + ' cm)') : 'Plana'} • <strong>${weight.toFixed(2)} Kg</strong>`;
            earnBox.textContent = `+ ${qty} Bobina(s) registradas (${avgWeight.toFixed(2)} Kg/u)`;
            card.style.borderColor = '#0284c7';
            return;
        }

        if (!activeProduct || qty <= 0 || weight <= 0) {
            badge.innerHTML = '<span class="badge bg-secondary text-white">COLOCA EL PESO</span>';
            details.textContent = 'Promedio: 0.00 Kg/millar';
            earnBox.textContent = 'Suma a tu nómina: + $0.00 USD';
            card.style.borderColor = '#334155';
            return;
        }

        const isVariable = activeProduct.is_variable;
        const theoreticalUnit = activeProduct.theoretical || 1.0;
        const targetUnits = activeProduct.target || 5;

        const averageWeight = weight / qty;
        const tariffPerUnit = targetUnits > 0 ? (dailySalary / targetUnits) : 0;
        const totalEarn = qty * tariffPerUnit;

        earnBox.textContent = `Suma a tu nómina: + $${totalEarn.toFixed(2)} USD`;

        if (isVariable) {
            badge.innerHTML = '<span class="badge bg-info text-dark fw-bold">BOBINA VARIABLE</span>';
            details.textContent = `Peso unitario: ${averageWeight.toFixed(2)} Kg`;
            card.style.borderColor = '#0284c7';
            return;
        }

        // Desviación contra peso teórico
        const deviation = ((averageWeight - theoreticalUnit) / theoreticalUnit) * 100;
        const devFormatted = (deviation > 0 ? '+' : '') + deviation.toFixed(1) + '%';

        if (Math.abs(deviation) <= 3.0) {
            badge.innerHTML = '<span class="badge bg-success text-white fw-bold fs-6">🟢 GRADO B: ÓPTIMO</span>';
            details.innerHTML = `Promedio: <strong>${averageWeight.toFixed(3)} Kg</strong> (${devFormatted})`;
            card.style.borderColor = '#10b981';
        } else if (deviation > 3.0) {
            badge.innerHTML = '<span class="badge bg-warning text-dark fw-bold fs-6">🟡 GRADO A: SOBREPESO</span>';
            details.innerHTML = `Promedio: <strong>${averageWeight.toFixed(3)} Kg</strong> (${devFormatted})`;
            card.style.borderColor = '#f59e0b';
        } else {
            badge.innerHTML = '<span class="badge bg-danger text-white fw-bold fs-6">🔴 GRADO C: SUBCALIBRE</span>';
            details.innerHTML = `Promedio: <strong>${averageWeight.toFixed(3)} Kg</strong> (${devFormatted})`;
            card.style.borderColor = '#ef4444';
        }
    }

    function submitBatch(mode) {
        const qty = parseFloat(document.getElementById('quantityInput')?.value) || 0;
        const weight = parseFloat(document.getElementById('weightInput')?.value) || 0;

        if (isExtrusionMachineMode) {
            const width = parseFloat(document.getElementById('extrusionWidthInput')?.value) || 0;
            if (width <= 0) {
                alert('Por favor indica el ancho de la manga en centímetros.');
                document.getElementById('extrusionWidthInput')?.focus();
                return;
            }
            if (weight <= 0) {
                alert('Por favor ingresa el peso de la bobina en báscula.');
                document.getElementById('weightInput')?.focus();
                return;
            }

            document.getElementById('printModeInput').value = mode;
            document.getElementById('batchProductionForm').submit();
            return;
        }

        const prodId = document.getElementById('selectedProductId').value;

        if (!prodId) {
            alert('Por favor selecciona una medida o producto usando el buscador.');
            const searchInput = document.getElementById('productSearchInput');
            if (searchInput) {
                searchInput.focus();
                openProductDropdown();
            }
            return;
        }
        if (qty <= 0) {
            alert('Por favor ingresa una cantidad válida de millares.');
            document.getElementById('quantityInput').focus();
            return;
        }
        if (weight <= 0) {
            alert('Por favor ingresa el peso registrado en la báscula.');
            document.getElementById('weightInput').focus();
            return;
        }

        document.getElementById('printModeInput').value = mode;
        document.getElementById('batchProductionForm').submit();
    }

    // Inicializar vista al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        if (isExtrusionMachineMode) {
            updateExtrusionPreview();
        }
    });

    // Modal de edición rápida para pesajes precargados
    function openEditBatchModal(btn) {
        const id = btn.getAttribute('data-id');
        const prodName = btn.getAttribute('data-product-name');
        const qty = btn.getAttribute('data-quantity');
        const weight = btn.getAttribute('data-weight');

        const form = document.getElementById('editBatchForm');
        form.action = `/operario/produccion/batch/${id}`;
        
        document.getElementById('editModalProductName').value = prodName;
        document.getElementById('editModalQuantity').value = qty;
        document.getElementById('editModalWeight').value = weight;

        function updateAvg() {
            const q = parseFloat(document.getElementById('editModalQuantity').value) || 0;
            const w = parseFloat(document.getElementById('editModalWeight').value) || 0;
            const avg = q > 0 ? (w / q).toFixed(3) : '0.000';
            document.getElementById('editModalAvgWeight').textContent = `${avg} Kg / millar`;
        }

        document.getElementById('editModalQuantity').oninput = updateAvg;
        document.getElementById('editModalWeight').oninput = updateAvg;
        updateAvg();

        const modal = new bootstrap.Modal(document.getElementById('editBatchModal'));
        modal.show();
    }

    // Inicializar producto compatible por defecto
    document.addEventListener('DOMContentLoaded', () => {
        const pool = getAvailableProductsPool();
        if (pool.length > 0) {
            selectProductById(pool[0].id, false);
        } else {
            // Si no hay productos compatibles o catálogo vacío, dejar el buscador limpio sin alertas molestas
            activeProduct = null;
            const searchInput = document.getElementById('productSearchInput');
            if (searchInput) searchInput.value = '';
            const prodIdInput = document.getElementById('selectedProductId');
            if (prodIdInput) prodIdInput.value = '';
            const badge = document.getElementById('selectedProductBadge');
            if (badge) {
                if (mountedBobinaSpec) {
                    badge.className = 'badge bg-warning text-dark';
                    badge.innerHTML = '⚠️ Sin bolsa compatible seleccionada';
                } else {
                    badge.className = 'badge bg-secondary text-white';
                    badge.innerHTML = 'Ninguna medida seleccionada';
                }
            }
        }
    });
</script>

{{-- MODAL PARA EDITAR PESAJE PRE-CARGADO --}}
<div class="modal fade" id="editBatchModal" tabindex="-1" aria-labelledby="editBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-white" style="background: #1e293b; border: 1px solid #3b82f6; border-radius: 14px;">
            <div class="modal-header border-secondary py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="editBatchModalLabel">
                    <span>✏️</span> Corregir Pesaje Pre-cargado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="editBatchForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 bg-opacity-25 bg-warning text-warning small py-2 px-3 mb-3 d-flex align-items-center gap-2">
                        <span>ℹ️</span>
                        <span>Solo puedes editar este pesaje porque aún <strong>no ha sido impreso</strong>. Al guardar se recalcularán automáticamente la calidad y tu ganancia.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small fw-bold">Producto / Medida</label>
                        <input type="text" id="editModalProductName" class="form-control bg-dark text-white border-secondary fw-bold" readonly disabled>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label text-white-50 small fw-bold">Cantidad (Millares / Bultos)</label>
                            <input type="number" step="1" min="1" max="1000" name="quantity" id="editModalQuantity" class="form-control bg-dark text-white border-secondary text-center fw-bold fs-5" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-white-50 small fw-bold">Peso Total en Báscula (Kg)</label>
                            <input type="number" step="0.01" min="0.01" max="5000" name="weight" id="editModalWeight" class="form-control bg-dark text-warning border-secondary text-center fw-bold fs-5" required>
                        </div>
                    </div>

                    <div class="mt-3 p-2 rounded bg-black bg-opacity-40 border border-secondary text-center small text-white-50">
                        Peso Promedio por Millar Resultante: <strong id="editModalAvgWeight" class="text-info font-monospace">-- Kg</strong>
                    </div>
                </div>
                <div class="modal-footer border-secondary py-2">
                    <button type="button" class="btn btn-secondary btn-sm fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">💾 Guardar Corrección</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL PARA REGISTRAR MERMA / PURGA / DEFECTO --}}
<div class="modal fade" id="wasteRecordModal" tabindex="-1" aria-labelledby="wasteRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-white" style="background: #1e293b; border: 1px solid #f59e0b; border-radius: 14px;">
            <div class="modal-header border-secondary py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2 text-warning" id="wasteRecordModalLabel">
                    <span>⚠️</span> Registrar Merma / Purga / Defecto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="{{ route('operator.store_batch') }}" method="POST">
                @csrf
                <input type="hidden" name="is_waste_record" value="1">
                <input type="hidden" name="machine_id" value="{{ $activeShift?->machine_id }}">
                @if(!$user->isOperator())
                    <input type="hidden" name="operator_id" value="{{ $targetUser->id }}">
                @endif
                @if(!empty($mountedBobina))
                    <input type="hidden" name="source_bobina_id" value="{{ $mountedBobina->id }}">
                @endif
                <div class="modal-body p-4">
                    @if($isExtrusionMachine)
                        {{-- MODO EXTRUSIÓN: PURGA DE CALIBRACIÓN --}}
                        <input type="hidden" name="waste_type" value="purga_calibracion_extrusion">
                        <div class="alert alert-warning border-0 bg-opacity-25 bg-warning text-warning small py-2 px-3 mb-3">
                            <span>ℹ️</span> <strong>Purga de Boquilla / Calibración:</strong> Registra los kilogramos de material fundido descartado durante el ajuste del calibre o arranque de la extrusora.
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white-50 small fw-bold">🧪 Fórmula de Mezcla Usada:</label>
                            <select name="production_formula_id" class="form-select bg-dark text-white border-secondary">
                                <option value="">-- Sin Fórmula Específica --</option>
                                @foreach($formulas as $form)
                                    <option value="{{ $form->id }}">{{ $form->name }} ({{ $form->code ?? 'S/C' }})</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        {{-- MODO SELLADORA: DEFECTO DE BOBINA O MERMA OPERATIVA --}}
                        <div class="mb-3">
                            <label class="form-label text-white fw-bold small">Origen / Causa de la Merma:</label>
                            <select name="waste_type" id="wasteTypeSelect" class="form-select bg-dark text-white border-warning fw-bold" onchange="toggleWasteTypeHelp(this.value)">
                                <option value="defecto_extrusion" selected>💥 Defecto de Bobina / Extrusión (Material con falla, descartado)</option>
                                <option value="merma_operativa_sellado">✂️ Merma Operativa de Selladora (Ajuste de cuchilla, temperatura, troquel)</option>
                            </select>
                        </div>

                        @if(!empty($mountedBobina))
                            <div class="p-3 mb-3 rounded border border-warning bg-black bg-opacity-40" id="bobinaInfoAlert">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-warning small">📦 Bobina Montada Afectada:</strong>
                                    <span class="badge bg-warning text-dark font-monospace">{{ $mountedBobina->effective_batch_code }}</span>
                                </div>
                                <div class="text-white-50 small">
                                    <span>Operador Extrusor: <strong class="text-white">{{ $mountedBobina->user?->name ?? 'N/D' }}</strong></span> •
                                    <span>Saldo Actual: <strong class="text-info">{{ number_format($mountedBobina->remaining_weight, 2) }} Kg</strong></span>
                                </div>
                                <div class="mt-2 text-warning small" id="wasteNoticeHelp" style="font-size: 11px;">
                                    🛡️ <strong>Protección de Nómina:</strong> Esta merma se descontará del saldo de la bobina del extrusor y <u>NO penalizará</u> tu rendimiento ni tu destajo como sellador.
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info py-2 px-3 small mb-3">
                                <span>ℹ️ No hay bobina montada en este momento. La merma se registrará como ajuste operativo de selladora.</span>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label text-white-50 small fw-bold">📝 Motivo / Observaciones del Defecto:</label>
                            <input type="text" name="waste_notes" class="form-control bg-dark text-white border-secondary" placeholder="Ej: Calibre irregular, manga pegada, fuelle torcido, corte disparejo...">
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label text-white fw-bold small">⚖️ Kilos de Merma / Purga (Kg):</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-warning text-dark fw-bold">Kg</span>
                            <input type="number" step="0.01" min="0.01" max="1000" name="waste_weight_kg" class="form-control bg-dark text-warning border-warning fw-bold fs-4 text-center" placeholder="0.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary py-2">
                    <button type="button" class="btn btn-secondary btn-sm fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold px-3">⚠️ Guardar Registro de Merma</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleWasteTypeHelp(val) {
    const notice = document.getElementById('wasteNoticeHelp');
    if (!notice) return;
    if (val === 'defecto_extrusion') {
        notice.innerHTML = '🛡️ <strong>Protección de Nómina:</strong> Esta merma se descontará del saldo de la bobina del extrusor y <u>NO penalizará</u> tu rendimiento ni tu destajo como sellador.';
        notice.className = 'mt-2 text-warning small';
    } else {
        notice.innerHTML = '✂️ <strong>Ajuste de Selladora:</strong> Merma operativa producida durante el cuadre de cuchilla, calor o troquel en esta máquina selladora.';
        notice.className = 'mt-2 text-info small';
    }
}
</script>
@endsection
