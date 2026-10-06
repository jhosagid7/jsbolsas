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

                <div class="row g-3">
                    {{-- Columna 1: Selección de Producto / Medida --}}
                    <div class="col-12 col-lg-5">
                        <label class="form-label text-white fw-bold small d-flex justify-content-between align-items-center mb-1">
                            <span>1. Medida / Producto:</span>
                            <span class="badge bg-secondary text-white" id="selectedProductBadge">Ninguna medida seleccionada</span>
                        </label>

                        {{-- Accesos Rápidos de Medidas Populares --}}
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <span class="text-white-50 small align-self-center me-1" style="font-size: 10px;">Frecuentes:</span>
                            @foreach($products->take(6) as $fastProd)
                                <button type="button" class="btn btn-outline-secondary btn-sm text-truncate text-white fast-prod-btn py-0 px-2"
                                        style="font-size: 11px; max-width: 140px; border-radius: 6px;"
                                        onclick="selectProductById({{ $fastProd->id }})">
                                    {{ $fastProd->name }}
                                </button>
                            @endforeach
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
                            <tr>
                                <td class="ps-3 font-monospace text-white-50">
                                    {{ $prod->recorded_at ? $prod->recorded_at->format('h:i A') : '--' }}
                                </td>
                                <td>
                                    <strong class="text-white">{{ $prod->product->name ?? 'Bolsa' }}</strong>
                                    <span class="text-white-50 small d-block">{{ $prod->product->sku ?? '' }}</span>
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
                                    @php $grade = strtoupper($prod->weight_quality_grade ?: 'B'); @endphp
                                    @if($grade === 'A')
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
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku ?? '',
                'sale_unit' => $p->sale_unit ?? '',
                'theoretical' => (float)($p->unit_weight_kg > 0 ? $p->unit_weight_kg : 1.0),
                'target' => $t,
                'millar' => (float)($p->millar_per_bulto ?: 1),
                'is_variable' => (bool)$p->is_variable_quantity,
                'tariff' => round($tf, 2),
            ];
        }
        $productsCatalogJson = json_encode($catalogItems);
    }
@endphp

{{-- SCRIPT REACTIVO DE CÁLCULO Y BUSCADOR INTELIGENTE (JS VANILLA) --}}
<script>
    const dailySalary = {{ (float)($targetUser->daily_salary ?: 15.0) }};

    // Catálogo completo de productos para filtrado reactivo instantáneo (sub-milisegundo)
    const productsCatalog = {!! $productsCatalogJson !!};

    // Precomputar cadena de búsqueda normalizada (sin acentos, minúsculas)
    function normalizeStr(str) {
        return (str || '')
            .toString()
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    productsCatalog.forEach(p => {
        p._searchStr = normalizeStr(`${p.name} ${p.sku} ${p.sale_unit}`);
    });

    let currentFiltered = [];
    let highlightedIndex = -1;
    let activeProduct = null;

    function setQty(val) {
        document.getElementById('quantityInput').value = val;
        recalcGrading();
    }

    // Compatibilidad con selectProduct previo
    function selectProduct(id) {
        selectProductById(id);
    }

    function selectProductById(id) {
        const prod = productsCatalog.find(p => p.id == id);
        if (!prod) return;

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

        // Actualizar Badge de Estado
        const badge = document.getElementById('selectedProductBadge');
        if (badge) {
            badge.className = 'badge bg-success text-white';
            badge.innerHTML = `✓ ${prod.name}`;
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

        const qNorm = normalizeStr(query.trim());
        const tokens = qNorm.split(/\s+/).filter(Boolean);

        if (!tokens.length) {
            // Mostrar los primeros 25 productos como sugerencias iniciales
            currentFiltered = productsCatalog.slice(0, 25);
        } else {
            // Filtrar productos que contengan todos los tokens (búsqueda multi-palabra inteligente)
            currentFiltered = productsCatalog.filter(p => tokens.every(tok => p._searchStr.includes(tok)));
        }

        if (resetHighlight) {
            highlightedIndex = currentFiltered.length > 0 ? 0 : -1;
        }

        if (currentFiltered.length === 0) {
            dropdown.innerHTML = `
                <div class="p-3 text-center text-white-50">
                    <div class="fs-4 mb-1">🔍</div>
                    <div class="fw-bold">No se encontraron productos coincidentes</div>
                    <small>Prueba buscando con otra medida (ej: 40x60, 30x40, cristal)</small>
                </div>
            `;
            return;
        }

        let html = '';
        if (!tokens.length) {
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

            html += `
                <div class="product-search-item px-3 py-2 border-bottom border-secondary border-opacity-25 text-white d-flex justify-content-between align-items-center ${isHighlighted ? 'active' : ''} ${isSelected ? 'bg-primary bg-opacity-25' : ''}"
                     id="searchItem-${idx}"
                     onmouseenter="setHighlightedIndex(${idx})"
                     onclick="selectProductById(${p.id})">
                    <div class="me-2 text-truncate">
                        <div class="fw-bold text-white fs-6 text-truncate">
                            ${highlightedName}
                        </div>
                        <div class="text-white-50 small d-flex flex-wrap gap-2 align-items-center mt-1" style="font-size: 11px;">
                            ${p.sku ? `<span class="badge bg-secondary font-monospace">${highlightedSku}</span>` : ''}
                            <span>⚖️ Teórico: <strong class="text-info">${p.theoretical.toFixed(3)} Kg</strong>/mill</span>
                            <span>💰 Tarifa: <strong class="text-success">$${p.tariff.toFixed(2)} USD</strong></span>
                        </div>
                    </div>
                    <div class="text-end ps-2 flex-shrink-0">
                        <span class="badge bg-dark border border-secondary text-info fw-bold">
                            Meta: ${p.target} m/turno
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

    function recalcGrading() {
        const qty = parseFloat(document.getElementById('quantityInput')?.value) || 0;
        const weight = parseFloat(document.getElementById('weightInput')?.value) || 0;

        const badge = document.getElementById('gradingBadge');
        const details = document.getElementById('gradingDetails');
        const card = document.getElementById('gradingCard');
        const earnBox = document.getElementById('estimatedEarnBox');

        if (!badge || !details || !card || !earnBox) return;

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
        const prodId = document.getElementById('selectedProductId').value;
        const qty = parseFloat(document.getElementById('quantityInput').value) || 0;
        const weight = parseFloat(document.getElementById('weightInput').value) || 0;

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

    // Inicializar producto por defecto (si hay productos disponibles)
    document.addEventListener('DOMContentLoaded', () => {
        if (productsCatalog.length > 0) {
            selectProductById(productsCatalog[0].id);
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
@endsection
