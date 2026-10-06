@extends('layouts.app')
@section('title', 'Costos, Simulación de Precios y Rendimiento')

@section('content')
<style>
    @keyframes livePulse {
        0% { background-color: rgba(250, 204, 21, 0.45); color: #fff; transform: scale(1.08); }
        100% { background-color: transparent; transform: scale(1); }
    }
    .cell-live-updated {
        animation: livePulse 1.6s ease-out;
        border-radius: 4px;
        display: inline-block;
        padding: 0 4px;
    }
    .table-clean th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #94a3b8;
        background: #0f172a;
        padding: 12px 14px;
        border-bottom: 2px solid rgba(255,255,255,0.1);
    }
    .table-clean td {
        padding: 12px 14px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-size: 13.5px;
    }
    .table-clean tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03);
    }
    .price-pill {
        display: inline-block;
        font-family: monospace;
        font-weight: 700;
        font-size: 13px;
    }
    .info-tooltip-btn {
        color: #38bdf8 !important;
        cursor: pointer;
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s ease;
        vertical-align: middle;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        padding: 0;
        border: none;
        background: transparent;
    }
    .info-tooltip-btn:hover, .info-tooltip-btn:focus {
        color: #facc15 !important;
        transform: scale(1.25);
        outline: none;
    }
    .dark-info-popover {
        --bs-popover-bg: #0f172a;
        --bs-popover-border-color: rgba(56, 189, 248, 0.35);
        --bs-popover-header-bg: #1e293b;
        --bs-popover-header-color: #facc15;
        --bs-popover-body-color: #f8fafc;
        --bs-popover-box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.7), 0 10px 15px -5px rgba(0, 0, 0, 0.5);
        --bs-popover-border-radius: 12px;
        max-width: 330px;
        font-size: 12.5px;
        line-height: 1.55;
        z-index: 1060;
    }
    .dark-info-popover .popover-header {
        font-weight: 700;
        font-size: 13.5px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 10px 14px;
        background-color: #1e293b;
        color: #facc15;
        border-top-left-radius: 11px;
        border-top-right-radius: 11px;
    }
    .dark-info-popover .popover-body {
        padding: 12px 14px;
        color: #e2e8f0;
        background-color: #0f172a;
        border-bottom-left-radius: 11px;
        border-bottom-right-radius: 11px;
    }
    .dark-info-popover .popover-body strong {
        color: #38bdf8;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">💰 Control de Costos & Simulación de Precios</h3>
        <p class="text-white-50 mb-0">Catálogo técnico unificado: costos de mezcla por kilo, especificaciones físicas y metas de utilidad diaria.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('formulas.index') }}" class="btn btn-outline-warning fw-bold">
            <i class="bi bi-bezier2 me-1"></i> Fórmulas de Mezcla
        </a>
        @if(Auth::user()->isSuperAdmin())
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#newProductModal">
                <i class="bi bi-plus-circle me-1"></i> Nueva Ficha Técnica
            </button>
        @endif
    </div>
</div>

<!-- Configuración Global de Costos Reactiva -->
<div class="card-custom mb-4 border-start border-primary border-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-white mb-0">
            <i class="bi bi-sliders text-info me-2"></i> Parámetros Financieros Globales de Fábrica
        </h6>
        @if(Auth::user()->isSuperAdmin())
            <span class="badge bg-success font-monospace" style="font-size: 11px;">
                <i class="bi bi-lightning-charge-fill me-1"></i> Recálculo Reactivo en Vivo
            </span>
        @endif
    </div>

    <form id="globalCostsForm" action="{{ route('costs.update') }}" method="POST">
        @csrf
        <div class="row g-3 align-items-end">
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="form-label small text-white-50 fw-bold d-flex align-items-center justify-content-between mb-1">
                    <span>Costo de Resina ($/KG)</span>
                    <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                            data-bs-toggle="popover" 
                            data-bs-trigger="hover focus" 
                            data-bs-placement="top" 
                            data-bs-html="true" 
                            data-bs-custom-class="dark-info-popover shadow-lg" 
                            title="ℹ️ Costo de Resina ($/KG)" 
                            data-bs-content="Este campo indica el costo promedio por kilogramo del plástico utilizado para este producto.<br><br><strong>¿Cómo se usa?</strong> El sistema lo jala de forma automática basándose en la mezcla de materiales que asignaste en el módulo de fórmulas, por lo que normalmente no necesitas modificarlo de manera manual."
                            tabindex="0"
                            aria-label="Información sobre Costo de Resina">
                        <i class="bi bi-info-circle-fill"></i>
                    </button>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-warning fw-bold">$</span>
                    <input type="number" step="any" name="resin_price_per_kg" id="global_resin_price" class="form-control text-warning fw-bold fs-5" value="{{ $settings->resin_price_per_kg }}" {{ Auth::user()->isSuperAdmin() ? 'required' : 'readonly' }}>
                </div>
                <small class="text-muted" style="font-size: 11px;">Fallback para productos sin receta</small>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="form-label small text-white-50 fw-bold d-flex align-items-center justify-content-between mb-1">
                    <span>Costo Fijo por Turno</span>
                    <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                            data-bs-toggle="popover" 
                            data-bs-trigger="hover focus" 
                            data-bs-placement="top" 
                            data-bs-html="true" 
                            data-bs-custom-class="dark-info-popover shadow-lg" 
                            title="ℹ️ Costo Fijo por Turno" 
                            data-bs-content="Representa los costos operativos estimados para mantener la máquina encendida durante un turno de trabajo (ej. el sueldo del operador por esas horas y el consumo estimado de luz).<br><br><strong>¿Cómo se usa?</strong> Ingresa este valor para calcular la ganancia real neta del turno. Si solo quieres ver la ganancia directa del plástico sin restar gastos operativos, puedes dejar este campo en <strong>0</strong>."
                            tabindex="0"
                            aria-label="Información sobre Costo Fijo por Turno">
                        <i class="bi bi-info-circle-fill"></i>
                    </button>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-info fw-bold">$</span>
                    <input type="number" step="any" name="shift_fixed_cost" id="global_shift_fixed_cost" class="form-control text-info fw-bold fs-5" value="{{ $settings->shift_fixed_cost }}" {{ Auth::user()->isSuperAdmin() ? 'required' : 'readonly' }}>
                </div>
                <small class="text-muted" style="font-size: 11px;">Mano de obra + Energía / turno</small>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="form-label small text-white-50 fw-bold d-flex align-items-center justify-content-between mb-1">
                    <span>Meta Utilidad Diaria ($ USD/Día)</span>
                    <button type="button" class="btn btn-link p-0 info-tooltip-btn text-decoration-none" 
                            data-bs-toggle="popover" 
                            data-bs-trigger="hover focus" 
                            data-bs-placement="top" 
                            data-bs-html="true" 
                            data-bs-custom-class="dark-info-popover shadow-lg" 
                            title="ℹ️ Meta Utilidad Diaria ($ USD/Día)" 
                            data-bs-content="Es la ganancia diaria neta que el dueño o administrador espera obtener por el funcionamiento de esa máquina o la venta de este producto específico.<br><br><strong>¿Cómo se usa?</strong> Escribe aquí tu meta económica deseada (ej. <strong>105.00</strong> USD) para que el 'Calculador Inverso' te sugiera de forma automática a qué precio de fábrica debes vender cada bulto o millar de bolsa para alcanzarla."
                            tabindex="0"
                            aria-label="Información sobre Meta Utilidad Diaria">
                        <i class="bi bi-info-circle-fill"></i>
                    </button>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-success fw-bold">$</span>
                    <input type="number" step="any" name="daily_profit_target" id="global_daily_profit_target" class="form-control text-success fw-bold fs-5" value="{{ $settings->daily_profit_target }}" {{ Auth::user()->isSuperAdmin() ? 'required' : 'readonly' }}>
                </div>
                <small class="text-muted" style="font-size: 11px;">Ganancia diaria objetivo</small>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                @if(Auth::user()->isSuperAdmin())
                    <button type="submit" id="globalSubmitBtn" class="btn btn-warning fw-bold w-100 py-2 shadow-sm">
                        <i class="bi bi-arrow-repeat me-1"></i> Guardar y Recalcular
                    </button>
                @else
                    <button type="button" class="btn btn-secondary w-100 py-2" disabled>🔒 Protegido</button>
                @endif
            </div>
        </div>
    </form>

    <!-- Notificación Toast Reactiva en Vivo -->
    <div id="liveFeedbackAlert" class="alert alert-success mt-3 py-2 px-3 small fw-bold align-items-center justify-content-between shadow-sm" style="display: none; border-radius: 10px; background-color: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399;">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-5">✨</span>
            <span id="liveFeedbackMessage">¡Parámetros guardados y catálogo recalculado al instante!</span>
        </div>
        <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('liveFeedbackAlert').style.display='none'"></button>
    </div>
</div>

<!-- Tabla Principal Limpia y Ordenada -->
<div class="card-custom">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-white mb-0">
                <i class="bi bi-table text-info me-2"></i> Catálogo de Precios y Rendimiento
            </h5>
            <small class="text-white-50" id="productsCounter">Mostrando {{ $products->count() }} fichas</small>
        </div>
        <span class="badge bg-dark border border-info text-info font-monospace py-2 px-3 fs-6">
            📦 {{ $products->count() }} Productos
        </span>
    </div>

    <!-- Buscador Inteligente Directo -->
    <div class="row g-2 mb-3 align-items-center">
        <div class="col-12 col-md-8 col-lg-9">
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-info fs-5">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="smartProductSearch" class="form-control bg-dark text-white border-secondary fs-6 py-2" placeholder="🔍 Escribe para buscar (ej. '2565', 'bambi 1kg', 'c18', '60x90')..." autocomplete="off">
                <button type="button" id="clearSearchBtn" class="btn btn-outline-secondary px-3" style="display: none;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-3 text-end">
            <span class="badge bg-secondary py-2 py-md-3 w-100 fs-6" id="filterStatusBadge">Mostrando Todos ({{ $products->count() }})</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-clean mb-0 align-middle">
            <thead>
                <tr>
                    <th style="min-width: 260px;">Producto & Ficha Técnica</th>
                    <th>Fórmula ($/KG)</th>
                    <th>Presentación & Peso</th>
                    <th>Costo Mat.</th>
                    <th>Precio Fábrica</th>
                    <th>Meta / Turno</th>
                    <th class="text-end">Distribuidor (+10%)</th>
                    <th class="text-end">Mayorista (+17%)</th>
                    <th class="text-end">Minorista (+21%)</th>
                    @if(Auth::user()->isSuperAdmin())
                        <th class="text-center" style="width: 100px;">Simular</th>
                    @endif
                </tr>
            </thead>
            <tbody id="productsTableBody">
                @forelse($products as $p)
                    @php
                        $priceKg = $p->getEffectivePricePerKg((float)$settings->resin_price_per_kg);
                        $pesoFisico = $p->calculatePhysicalWeight();
                        $pesoReal = $p->calculateRealTotalWeight();
                        $costo = $p->calculateRawMaterialCost((float)$settings->resin_price_per_kg);
                        $fabrica = (float)($p->price > 0 ? $p->price : $p->simulateFactoryPriceFromDailyTarget());
                        $tiers = $p->calculateTiersFromFactoryPrice($fabrica);
                        $metaUnits = (int)($p->target_units_per_shift ?: 5);
                        $utilDia = $p->calculateDailyProfitFromFactoryPrice($fabrica, $metaUnits, $costo);

                        // Crear texto searchable completo con combinaciones de dimensiones sin 'X' o con guiones
                        $dimClean = $p->width_inch && $p->length_inch ? ($p->width_inch . 'x' . $p->length_inch . ' ' . $p->width_inch . $p->length_inch) : '';
                        $searchableData = $p->name . ' ' . ($p->category ?? '') . ' ' . ($p->sku ?? '') . ' ' . $dimClean . ' ' . ($p->gauge_caliber ?? '') . ' ' . ($p->formula?->name ?? '') . ' ' . $p->sale_unit . ' ' . ($p->is_variable_quantity ? 'bobina rollo kg kilo' : '');
                    @endphp
                    <tr class="product-row" data-id="{{ $p->id }}" data-category="{{ strtolower($p->category ?? 'general') }}" data-search="{{ strtolower($searchableData) }}">
                        <!-- 1. Producto y Especificaciones -->
                        <td>
                            <strong class="text-white fs-6 d-block mb-1">{{ $p->name }}</strong>
                            <div class="d-flex align-items-center gap-1 flex-wrap" style="font-size: 11px;">
                                <span class="badge bg-dark border border-secondary text-info">{{ $p->category ?? 'General' }}</span>
                                @if($p->is_variable_quantity)
                                    <span class="badge bg-warning text-dark fw-bold">🔄 BOBINA POR KG</span>
                                @elseif($p->width_inch && $p->length_inch)
                                    <span class="text-white-50">{{ $p->width_inch }}×{{ $p->length_inch }} cm</span>
                                    @if($p->gauge_caliber)
                                        <span class="text-warning">C-{{ $p->gauge_caliber }}</span>
                                    @endif
                                @endif
                                @if($p->sku)
                                    <span class="text-white-50 font-monospace">({{ $p->sku }})</span>
                                @endif
                            </div>
                        </td>

                        <!-- 2. Fórmula -->
                        <td>
                            @if($p->formula && $p->formula->currentVersion)
                                <span class="badge bg-warning text-dark font-monospace fw-bold">🧪 {{ $p->formula->name }}</span>
                                <small class="text-success font-monospace fw-bold d-block mt-1" id="pkg_cell_{{ $p->id }}">${{ number_format($p->formula->currentVersion->cost_per_kg, 4) }}/kg</small>
                            @else
                                <span class="badge bg-secondary">Resina Genérica</span>
                                <small class="text-white-50 font-monospace d-block mt-1" id="pkg_cell_{{ $p->id }}">${{ number_format($settings->resin_price_per_kg, 3) }}/kg</small>
                            @endif
                        </td>

                        <!-- 3. Presentación & Peso -->
                        <td>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                @if($p->is_variable_quantity)
                                    <span class="badge bg-success fw-bold">KG</span>
                                @else
                                    <span class="badge bg-info text-dark fw-bold">{{ $p->sale_unit ?? 'BULTO' }}</span>
                                    @if($p->millar_per_bulto)
                                        <small class="text-white-50 font-monospace">({{ $p->millar_per_bulto }} mil)</small>
                                    @endif
                                @endif
                            </div>
                            <div class="d-flex flex-column">
                                @if(!$p->is_variable_quantity && $p->calculatePhysicalWeight() > 0)
                                    <small class="text-white-50 font-monospace" style="font-size: 10px;">
                                        Teórico: <span class="text-secondary">{{ number_format($p->calculatePhysicalWeight(), 3) }} Kg/mil</span>
                                    </small>
                                @endif
                                <small class="text-white-50 font-monospace">
                                    PESO_R: <strong class="text-white">{{ number_format($pesoReal, 3) }} Kg</strong>
                                </small>
                            </div>
                        </td>

                        <!-- 4. Costo Materia Prima -->
                        <td>
                            <span class="text-warning fw-bold font-monospace fs-6" id="cost_cell_{{ $p->id }}">${{ number_format($costo, 3) }}</span>
                        </td>

                        <!-- 5. Precio Fábrica -->
                        <td>
                            <span class="text-info fw-bold font-monospace fs-6" id="fabrica_cell_{{ $p->id }}">${{ number_format($fabrica, 2) }}</span>
                            <small class="text-white-50 d-block" style="font-size: 10px;">{{ $p->is_variable_quantity ? '/kg' : '/und' }}</small>
                        </td>

                        <!-- 6. Meta de Turno & Utilidad -->
                        <td>
                            <span class="badge bg-dark border border-warning text-warning fw-bold">
                                🎯 {{ $metaUnits }} {{ $p->is_variable_quantity ? 'BOBINAS' : $p->sale_unit }}
                            </span>
                            <small class="text-{{ $utilDia >= 0 ? 'success' : 'danger' }} fw-bold font-monospace d-block mt-1" id="utildia_cell_{{ $p->id }}">
                                +${{ number_format($utilDia, 2) }}/día
                            </small>
                        </td>

                        <!-- 7. Precios I, II, III (Distribuidor, Mayorista, Minorista) -->
                        <td class="text-end">
                            <span class="text-success fw-bold font-monospace fs-6" id="tier1_cell_{{ $p->id }}">${{ number_format($tiers['tier_1'], 2) }}</span>
                        </td>
                        <td class="text-end">
                            <span class="text-success fw-bold font-monospace fs-6" id="tier2_cell_{{ $p->id }}">${{ number_format($tiers['tier_2'], 2) }}</span>
                        </td>
                        <td class="text-end">
                            <span class="text-success fw-bold font-monospace fs-6" id="tier3_cell_{{ $p->id }}">${{ number_format($tiers['tier_3'], 2) }}</span>
                        </td>

                        <!-- 8. Botón de Simulación -->
                        @if(Auth::user()->isSuperAdmin())
                            <td class="text-center">
                                <button class="btn btn-outline-warning btn-sm fw-bold px-3 py-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $p->id }}" style="border-radius: 8px;">
                                    <i class="bi bi-calculator"></i> Simular
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-white-50 py-4">No hay fichas técnicas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Alerta de No Resultados -->
        <div id="noProductsFoundAlert" class="p-5 text-center text-white-50" style="display: none;">
            <i class="bi bi-search fs-1 d-block mb-2 text-warning"></i>
            <h5>No se encontraron productos que coincidan con la búsqueda.</h5>
            <p class="small text-muted">Prueba buscando solo números de medidas (ej. "2565"), parte del nombre en cualquier orden (ej. "bambi 1kg") o calibre ("c18").</p>
        </div>
    </div>
</div>

<!-- Modales de Edición y Simulación Inversa (Fuera de la tabla) -->
@if(Auth::user()->isSuperAdmin())
    @foreach($products as $p)
        @php
            $priceKg = $p->getEffectivePricePerKg((float)$settings->resin_price_per_kg);
            $pesoFisico = $p->calculatePhysicalWeight();
            $pesoReal = $p->calculateRealTotalWeight();
            $costo = $p->calculateRawMaterialCost((float)$settings->resin_price_per_kg);
            $fabrica = (float)($p->price > 0 ? $p->price : $p->simulateFactoryPriceFromDailyTarget());
            $tiers = $p->calculateTiersFromFactoryPrice($fabrica);
            $metaUnits = (int)($p->target_units_per_shift ?: 5);
            $utilDia = $p->calculateDailyProfitFromFactoryPrice($fabrica, $metaUnits, $costo);
            $refMargins = $p->calculateReferenceMargins($costo);
        @endphp
        <div class="modal fade" id="editModal{{ $p->id }}" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content bg-dark text-white border-secondary">
                    <form action="{{ route('products.technical.update', $p->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title fw-bold text-warning">
                                <i class="bi bi-calculator me-2"></i> Simulador de Precios & Ficha Técnica: {{ $p->name }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <!-- Columna Izquierda: Especificación Física y Fórmula -->
                                <div class="col-lg-6 border-end border-secondary-subtle pe-lg-4">
                                    <h6 class="fw-bold text-info mb-3">1. Tipo de Producto, Fórmula & Especificaciones</h6>

                                    <!-- Switch de Modo Bobina / Venta por Kilo y Bulto Compuesto -->
                                    <div class="p-3 bg-black rounded border border-warning mb-3">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="is_variable_quantity" value="1" id="is_variable_{{ $p->id }}" {{ $p->is_variable_quantity ? 'checked' : '' }} onchange="toggleBobinaMode('{{ $p->id }}')">
                                            <label class="form-check-label fw-bold text-warning fs-6" for="is_variable_{{ $p->id }}">
                                                🔄 ¿Es Bobina / Venta por Kilo? (Peso Variable)
                                            </label>
                                            <small class="text-white-50 d-block mt-1 ms-4" style="font-size: 11px;">
                                                Al activar: se ocultan las medidas (ancho/largo), la unidad se fija en <strong>KG</strong> y el peso real en <strong>1.00 Kg</strong> para costear por kilogramo.
                                            </small>
                                        </div>

                                        <!-- Sub-opción: Bulto Compuesto de Bobinas -->
                                        <div class="mt-3 pt-2 border-top border-secondary-subtle" id="composite_rolls_box_{{ $p->id }}" style="{{ !$p->is_variable_quantity ? 'display:none;' : '' }}">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="is_composite_rolls" value="1" id="is_composite_{{ $p->id }}" {{ $p->is_composite_rolls ? 'checked' : '' }} onchange="toggleCompositeMode('{{ $p->id }}')">
                                                <label class="form-check-label fw-bold text-info fs-6" for="is_composite_{{ $p->id }}">
                                                    📦 ¿Es Bulto Compuesto de Bobinas? (Ej. Bambi 3.4 / 3.6 / 4.0)
                                                </label>
                                                <small class="text-white-50 d-block ms-4" style="font-size: 11px;">
                                                    Activa el agrupamiento de <strong>N bobinitas individuales</strong> de peso variable que juntas forman <strong>1 Bulto</strong>.
                                                </small>
                                            </div>

                                            <div class="p-3 bg-dark rounded border border-info ms-4 mt-2" id="suggested_rolls_container_{{ $p->id }}" style="{{ !$p->is_composite_rolls ? 'display:none;' : '' }}">
                                                <label class="form-label small text-info fw-bold mb-1 d-flex align-items-center justify-content-between">
                                                    <span><i class="bi bi-disc text-warning me-1"></i> Bobinas Sugeridas por Bulto (Referencia Estándar):</span>
                                                    <span class="badge bg-info text-dark font-monospace">1 Bulto = N Bobinas</span>
                                                </label>
                                                <div class="input-group input-group-sm" style="max-width: 200px;">
                                                    <input type="number" min="1" max="50" name="suggested_rolls_per_package" id="suggested_rolls_{{ $p->id }}" class="form-control bg-black text-warning font-monospace fw-bold fs-6" value="{{ $p->suggested_rolls_per_package ?? 9 }}">
                                                    <span class="input-group-text bg-secondary text-white fw-bold">Bobinas</span>
                                                </div>
                                                <small class="text-white-50 d-block mt-2" style="font-size: 10.5px;">
                                                    💡 <strong>Flexibilidad en Planta:</strong> Abre por defecto esta cantidad de casillas en la APK y báscula. El operario o supervisor podrá agregar o quitar bobinas (<strong>+ / -</strong>) dinámicamente según el pesaje real.
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning py-2 small mb-3" id="bobina_alert_{{ $p->id }}" style="{{ !$p->is_variable_quantity ? 'display:none;' : '' }}">
                                        <i class="bi bi-info-circle me-1"></i> <strong>Modo Bobina Activo:</strong> Venta y costeo por <strong>Kilogramo (KG)</strong>. El costo de la bobina será exactamente el costo ponderado $/KG de la mezcla.
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-7">
                                            <label class="form-label small text-white-50">Nombre del Producto</label>
                                            <input type="text" name="name" class="form-control" value="{{ $p->name }}" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-white-50">Categoría</label>
                                            <input type="text" name="category" class="form-control" value="{{ $p->category }}" placeholder="Ej. Bobinas / Vivero">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small text-white-50 fw-bold">Fórmula de Preparación de Mezcla (Módulo 2)</label>
                                        <select name="production_formula_id" class="form-select border-warning text-warning fw-bold" id="formula_select_{{ $p->id }}" onchange="recalculateProductSimulation('{{ $p->id }}')">
                                            <option value="" data-cost="{{ $settings->resin_price_per_kg }}">
                                                -- Usar Resina Genérica (${{ number_format($settings->resin_price_per_kg, 4) }}/kg) --
                                            </option>
                                            @foreach($formulas as $f)
                                                <option value="{{ $f->id }}" data-cost="{{ $f->currentVersion?->cost_per_kg ?? $settings->resin_price_per_kg }}" {{ $p->production_formula_id == $f->id ? 'selected' : '' }}>
                                                    🧪 {{ $f->name }} (${{ number_format($f->currentVersion?->cost_per_kg ?? 0, 4) }}/kg)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Contenedor de Dimensiones (Se oculta en Modo Bobina) -->
                                    <div class="row g-3 mb-3" id="dim_container_{{ $p->id }}" style="{{ $p->is_variable_quantity ? 'display:none;' : '' }}">
                                        <div class="col-md-4">
                                            <label class="form-label small text-white-50">Ancho (cm)</label>
                                            <input type="number" step="any" name="width_inch" class="form-control" value="{{ $p->width_inch }}" id="width_{{ $p->id }}" oninput="recalculateProductSimulation('{{ $p->id }}')">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-white-50">Largo (cm)</label>
                                            <input type="number" step="any" name="length_inch" class="form-control" value="{{ $p->length_inch }}" id="length_{{ $p->id }}" oninput="recalculateProductSimulation('{{ $p->id }}')">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-white-50">Calibre (grosor)</label>
                                            <input type="number" step="any" name="gauge_caliber" class="form-control text-warning fw-bold" value="{{ $p->gauge_caliber }}" id="gauge_{{ $p->id }}" oninput="recalculateProductSimulation('{{ $p->id }}')">
                                        </div>
                                    </div>

                                    <!-- Contenedor de Presentación Comercial y Factor de Escala -->
                                    <div class="row g-3 mb-3" id="pres_container_{{ $p->id }}">
                                        <div class="col-md-5">
                                            <label class="form-label small text-white fw-bold">
                                                <i class="bi bi-box-seam text-warning me-1"></i> Presentación Comercial (Preset)
                                            </label>
                                            <select class="form-select bg-dark text-warning border-secondary fw-bold" id="preset_{{ $p->id }}" onchange="applyPresentationPreset('{{ $p->id }}', this)">
                                                <option value="CUSTOM" data-unit="{{ $p->sale_unit ?? 'BULTO' }}" data-factor="{{ $p->millar_per_bulto ?? 1 }}">⚙️ Personalizado / Otro</option>
                                                <option value="BOBINA_BULTO" data-unit="KG" data-factor="1.0" data-composite="1" {{ ($p->is_variable_quantity && $p->is_composite_rolls) ? 'selected' : '' }}>📦 Bulto Compuesto de Bobinas (Bambi)</option>
                                                <option value="KG" data-unit="KG" data-factor="1.0" data-composite="0" {{ ($p->is_variable_quantity && !$p->is_composite_rolls) ? 'selected' : '' }}>⚖️ Bobina Suelta / Venta por KG</option>
                                                <option value="MILLAR" data-unit="MILLAR" data-factor="1.0" data-composite="0">📦 Millar Estándar (1.0)</option>
                                                <option value="BULTO_20" data-unit="BULTO" data-factor="20.0" data-composite="0">📦 Bulto (20 Millares)</option>
                                                <option value="BULTO_15" data-unit="BULTO" data-factor="15.0" data-composite="0">📦 Bulto (15 Millares)</option>
                                                <option value="BULTO_10" data-unit="BULTO" data-factor="10.0" data-composite="0">📦 Bulto (10 Millares)</option>
                                                <option value="BULTO_5" data-unit="BULTO" data-factor="5.0" data-composite="0">📦 Bulto (5 Millares)</option>
                                                <option value="BULTO_3" data-unit="BULTO" data-factor="3.0" data-composite="0">📦 Bulto (3 Millares)</option>
                                                <option value="BULTO_1" data-unit="BULTO" data-factor="1.0" data-composite="0">📦 Bulto (1 Millar)</option>
                                                <option value="MILLAR_G" data-unit="MILLAR/G" data-factor="0.1" data-composite="0">🛍️ 100 Bolsas (0.1 Millar/G)</option>
                                                <option value="MILLAR_S" data-unit="MILLAR/S" data-factor="0.5" data-composite="0">🛍️ 500 Bolsas (0.5 Millar/S)</option>
                                                <option value="MILLAR_PAL" data-unit="MILLAR/PAL" data-factor="0.75" data-composite="0">🛍️ 750 Bolsas (0.75 Millar/PAL)</option>
                                                <option value="MILLAR_V" data-unit="MILLAR/V" data-factor="0.2" data-composite="0">🌱 Bulto 1/2 KG (0.2 Millar/V)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small text-white-50">Unidad Base DB</label>
                                            <input type="text" name="sale_unit" class="form-control text-info fw-bold bg-dark border-secondary" id="unit_{{ $p->id }}" value="{{ $p->sale_unit ?? 'BULTO' }}" onchange="recalculateProductSimulation('{{ $p->id }}')">
                                        </div>
                                        <div class="col-md-4" id="millar_bulto_box_{{ $p->id }}" style="{{ $p->is_variable_quantity ? 'display:none;' : '' }}">
                                            <label class="form-label small text-white-50">Factor Escala (Mil/Bulto)</label>
                                            <input type="number" step="any" name="millar_per_bulto" class="form-control bg-dark border-secondary text-white font-monospace" value="{{ $p->millar_per_bulto ?? 1 }}" id="millar_bulto_{{ $p->id }}" oninput="recalculateProductSimulation('{{ $p->id }}')">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small text-white-50">
                                                Peso Teórico por Millar (PESO) <span class="badge bg-secondary">Auto</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       id="theoretical_weight_{{ $p->id }}" 
                                                       class="form-control bg-secondary text-white border-secondary fw-bold" 
                                                       value="{{ number_format($p->calculatePhysicalWeight(), 3, '.', '') }}" 
                                                       step="any" 
                                                       readonly>
                                                <span class="input-group-text bg-dark text-white-50 border-secondary">Kg/mil</span>
                                            </div>
                                            <small class="text-white-50" style="font-size: 10px;">Cálculo base: Ancho × Largo × Calibre</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-warning fw-bold">
                                                Peso Real Unidad Venta (PESO_R)
                                            </label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       step="any" 
                                                       name="unit_weight_kg" 
                                                       class="form-control border-warning text-warning fw-bold bg-dark" 
                                                       value="{{ number_format($pesoReal, 3, '.', '') }}" 
                                                       id="peso_r_{{ $p->id }}" 
                                                       oninput="recalculateProductSimulation('{{ $p->id }}', true)">
                                                <span class="input-group-text bg-dark text-warning border-warning fw-bold">Kg</span>
                                            </div>
                                            <small class="text-white-50" style="font-size: 10px;">PESO × Factor Escala (Editable)</small>
                                        </div>
                                    </div>

                                    <!-- Tarjeta de Costo Materia Prima Calculado -->
                                    <div class="p-3 bg-black rounded border border-warning mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-white-50 d-block">Costo Materia Prima Calculado (COSTO = PESO_R × $/KG):</small>
                                                <span class="fs-4 fw-bold text-warning font-monospace" id="costo_preview_{{ $p->id }}">${{ number_format($costo, 3) }}</span>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-white-50 d-block">$/KG Aplicado:</small>
                                                <span class="text-success font-monospace fw-bold" id="pkg_preview_{{ $p->id }}">${{ number_format($priceKg, 3) }}/kg</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Márgenes de Referencia Informativos del Excel -->
                                    <h6 class="fw-bold text-secondary mb-2 small text-uppercase">Márgenes de Referencia Informativos</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-dark border border-secondary-subtle mb-0 text-center" style="font-size: 11px;">
                                            <thead>
                                                <tr class="bg-black">
                                                    <th>40% (x1.40)</th>
                                                    <th>45% (x1.45)</th>
                                                    <th>50% (x1.50)</th>
                                                    <th>60% (x1.65)</th>
                                                    <th>2% (x1.73)</th>
                                                    <th>1.00 (x2.00)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="font-monospace text-info">
                                                    <td id="m_40_{{ $p->id }}">${{ number_format($refMargins['m_40'], 2) }}</td>
                                                    <td id="m_45_{{ $p->id }}">${{ number_format($refMargins['m_45'], 2) }}</td>
                                                    <td id="m_50_{{ $p->id }}">${{ number_format($refMargins['m_50'], 2) }}</td>
                                                    <td id="m_60_{{ $p->id }}">${{ number_format($refMargins['m_60'], 2) }}</td>
                                                    <td id="m_2_{{ $p->id }}">${{ number_format($refMargins['m_2'], 2) }}</td>
                                                    <td id="m_1_{{ $p->id }}">${{ number_format($refMargins['m_1'], 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Columna Derecha: Calculador Inverso de Precio Fábrica y Utilidad -->
                                <div class="col-lg-6 ps-lg-4">
                                    <h6 class="fw-bold text-success mb-3">2. Calculador Inverso de Precio de Fábrica & Metas</h6>

                                    <div class="p-3 bg-dark border border-success rounded mb-3">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small text-white-50 fw-bold">Meta Producción / Turno (PRODUCCIÓN)</label>
                                                <div class="input-group">
                                                    <input type="number" step="any" name="target_units_per_shift" class="form-control text-warning fw-bold fs-5" value="{{ $metaUnits }}" id="target_units_{{ $p->id }}" oninput="recalculateFromDailyTarget('{{ $p->id }}')" required>
                                                    <span class="input-group-text bg-black border-secondary text-white-50" id="target_unit_label_{{ $p->id }}">{{ $p->is_variable_quantity ? 'BOBINA' : $p->sale_unit }}</span>
                                                </div>
                                                <small class="text-white-50" style="font-size: 10px;">Bobinas, Bultos o Millares por turno</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-white-50 fw-bold">Meta Utilidad Deseada ($ USD / Día)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-black border-secondary text-success fw-bold">$</span>
                                                    <input type="number" step="any" name="target_daily_profit" class="form-control text-success fw-bold fs-5" value="{{ $p->target_daily_profit ?? 105.00 }}" id="target_profit_{{ $p->id }}" oninput="recalculateFromDailyTarget('{{ $p->id }}')">
                                                </div>
                                                <small class="text-white-50" style="font-size: 10px;">Utilidad diaria objetivo esperada</small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small text-white-50 fw-bold">Precio Salida de Fábrica (FABRICA = COSTO + Utilidad/PRODUCCIÓN)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-black border-secondary text-info fw-bold">$</span>
                                                <input type="number" step="any" name="price" class="form-control text-info fw-bold fs-4" value="{{ number_format($fabrica, 2, '.', '') }}" id="fabrica_input_{{ $p->id }}" oninput="recalculateFromFabricaInput('{{ $p->id }}')" required>
                                                <span class="input-group-text bg-black border-secondary text-info fw-bold" id="fabrica_unit_tag_{{ $p->id }}">{{ $p->is_variable_quantity ? '/ KG' : '/ UND' }}</span>
                                            </div>
                                            <small class="text-info" style="font-size: 11px;">
                                                💡 Puedes editar directamente el precio FABRICA o cambiar la Utilidad deseada arriba para simularlo.
                                            </small>
                                        </div>

                                        <div class="p-2 bg-black rounded border border-secondary text-center mb-2">
                                            <small class="text-white-50">Utilidad Unitaria: <strong class="text-success font-monospace" id="unit_profit_preview_{{ $p->id }}">${{ number_format($fabrica - $costo, 2) }}</strong> | Utilidad Diaria Proyectada: <strong class="text-success fs-5 font-monospace" id="daily_profit_preview_{{ $p->id }}">${{ number_format($utilDia, 2) }}</strong></small>
                                        </div>
                                    </div>

                                    <!-- Escala de Precios de Venta Calculada -->
                                    <h6 class="fw-bold text-info mb-2 small text-uppercase">3. Escala de Precios de Venta</h6>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <div class="p-2 bg-black rounded border border-success text-center">
                                                <small class="text-white-50 d-block" style="font-size: 10px;">PRECIO I (Distribuidor)</small>
                                                <strong class="text-success font-monospace fs-5" id="tier1_preview_{{ $p->id }}">${{ number_format($tiers['tier_1'], 2) }}</strong>
                                                <small class="text-white-50 d-block" style="font-size: 9px;">FABRICA × 1.10 (+10%)</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="p-2 bg-black rounded border border-success text-center">
                                                <small class="text-white-50 d-block" style="font-size: 10px;">PRECIO II (Mayorista)</small>
                                                <strong class="text-success font-monospace fs-5" id="tier2_preview_{{ $p->id }}">${{ number_format($tiers['tier_2'], 2) }}</strong>
                                                <small class="text-white-50 d-block" style="font-size: 9px;">FABRICA × 1.17 (+17%)</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="p-2 bg-black rounded border border-success text-center">
                                                <small class="text-white-50 d-block" style="font-size: 10px;">PRECIO III (Minorista)</small>
                                                <strong class="text-success font-monospace fs-5" id="tier3_preview_{{ $p->id }}">${{ number_format($tiers['tier_3'], 2) }}</strong>
                                                <small class="text-white-50 d-block" style="font-size: 9px;">FABRICA × 1.21 (+21%)</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-3">
                                        <div class="col-12">
                                            <label class="form-label small text-white-50">Código SKU Único</label>
                                            <input type="text" name="sku" class="form-control font-monospace" value="{{ $p->sku }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning btn-sm fw-bold">Guardar Ficha Técnica y Precios Simulados</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif

<!-- Modal para Crear Nueva Ficha Técnica -->
@if(Auth::user()->isSuperAdmin())
<div class="modal fade" id="newProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-dark text-white border-secondary">
            <form action="{{ route('products.store') }}" method="POST">
                @csrf
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-primary">
                        <i class="bi bi-plus-circle me-2"></i> Registrar Nueva Ficha Técnica de Bolsa / Bobina
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Columna Izquierda: Especificación Física y Fórmula -->
                        <div class="col-lg-6 border-end border-secondary-subtle pe-lg-4">
                            <h6 class="fw-bold text-info mb-3">1. Tipo de Producto, Fórmula & Especificaciones</h6>

                            <!-- Switch de Modo Bobina / Venta por Kilo -->
                            <div class="form-check form-switch mb-3 p-3 bg-black rounded border border-warning">
                                <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="is_variable_quantity" value="1" id="new_is_variable" onchange="toggleNewBobinaMode()">
                                <label class="form-check-label fw-bold text-warning fs-6" for="new_is_variable">
                                    🔄 ¿Es Bobina / Venta por Kilo? (Peso Variable)
                                </label>
                                <small class="text-white-50 d-block mt-1 ms-4" style="font-size: 11px;">
                                    Al activar: se ocultan las medidas (ancho/largo), la unidad se fija en <strong>KG</strong> y el peso real en <strong>1.00 Kg</strong> para costear por kilogramo.
                                </small>
                            </div>

                            <div class="alert alert-warning py-2 small mb-3" id="new_bobina_alert" style="display:none;">
                                <i class="bi bi-info-circle me-1"></i> <strong>Modo Bobina Activo:</strong> Venta y costeo por <strong>Kilogramo (KG)</strong>. El costo de la bobina será exactamente el costo ponderado $/KG de la mezcla.
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <label class="form-label small text-white-50">Nombre del Producto</label>
                                    <input type="text" name="name" class="form-control" placeholder="Ej. Bolsa 20x30 C1.5" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small text-white-50">Categoría</label>
                                    <input type="text" name="category" class="form-control" placeholder="Ej. Bobinas / Vivero">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-white-50 fw-bold">Fórmula de Preparación de Mezcla (Módulo 2)</label>
                                <select name="production_formula_id" class="form-select border-warning text-warning fw-bold" id="new_formula_select" onchange="recalculateNewProductSimulation()">
                                    <option value="" data-cost="{{ $settings->resin_price_per_kg }}">
                                        -- Usar Resina Genérica (${{ number_format($settings->resin_price_per_kg, 4) }}/kg) --
                                    </option>
                                    @foreach($formulas as $f)
                                        <option value="{{ $f->id }}" data-cost="{{ $f->currentVersion?->cost_per_kg ?? $settings->resin_price_per_kg }}">
                                            🧪 {{ $f->name }} (${{ number_format($f->currentVersion?->cost_per_kg ?? 0, 4) }}/kg)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Contenedor de Dimensiones (Se oculta en Modo Bobina) -->
                            <div class="row g-3 mb-3" id="new_dim_container">
                                <div class="col-md-4">
                                    <label class="form-label small text-white-50">Ancho (cm)</label>
                                    <input type="number" step="any" name="width_inch" class="form-control" placeholder="24" id="new_width" oninput="recalculateNewProductSimulation()">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-white-50">Largo (cm)</label>
                                    <input type="number" step="any" name="length_inch" class="form-control" placeholder="36" id="new_length" oninput="recalculateNewProductSimulation()">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-white-50">Calibre (grosor)</label>
                                    <input type="number" step="any" name="gauge_caliber" class="form-control text-warning fw-bold" placeholder="0.0035" id="new_gauge" oninput="recalculateNewProductSimulation()">
                                </div>
                            </div>

                            <!-- Contenedor de Presentación Comercial y Factor de Escala -->
                            <div class="row g-3 mb-3" id="new_pres_container">
                                <div class="col-md-5">
                                    <label class="form-label small text-white fw-bold">
                                        <i class="bi bi-box-seam text-warning me-1"></i> Presentación Comercial (Preset)
                                    </label>
                                    <select class="form-select bg-dark text-warning border-secondary fw-bold" id="preset_new" onchange="applyNewPresentationPreset(this)">
                                        <option value="BULTO_20" data-unit="BULTO" data-factor="20.0" selected>📦 Bulto (20 Millares)</option>
                                        <option value="BULTO_15" data-unit="BULTO" data-factor="15.0">📦 Bulto (15 Millares)</option>
                                        <option value="BULTO_10" data-unit="BULTO" data-factor="10.0">📦 Bulto (10 Millares)</option>
                                        <option value="BULTO_5" data-unit="BULTO" data-factor="5.0">📦 Bulto (5 Millares)</option>
                                        <option value="BULTO_3" data-unit="BULTO" data-factor="3.0">📦 Bulto (3 Millares)</option>
                                        <option value="BULTO_1" data-unit="BULTO" data-factor="1.0">📦 Bulto (1 Millar)</option>
                                        <option value="MILLAR" data-unit="MILLAR" data-factor="1.0">📦 Millar Estándar (1.0)</option>
                                        <option value="MILLAR_G" data-unit="MILLAR/G" data-factor="0.1">🛍️ 100 Bolsas (0.1 Millar/G)</option>
                                        <option value="MILLAR_S" data-unit="MILLAR/S" data-factor="0.5">🛍️ 500 Bolsas (0.5 Millar/S)</option>
                                        <option value="MILLAR_PAL" data-unit="MILLAR/PAL" data-factor="0.75">🛍️ 750 Bolsas (0.75 Millar/PAL)</option>
                                        <option value="MILLAR_V" data-unit="MILLAR/V" data-factor="0.2">🌱 Bulto 1/2 KG (0.2 Millar/V)</option>
                                        <option value="KG" data-unit="KG" data-factor="1.0">⚖️ Bobina / Venta por KG</option>
                                        <option value="CUSTOM" data-unit="BULTO" data-factor="1.0">⚙️ Personalizado / Otro</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-white-50">Unidad Base DB</label>
                                    <input type="text" name="sale_unit" class="form-control text-info fw-bold bg-dark border-secondary" id="new_sale_unit" value="BULTO" onchange="recalculateNewProductSimulation()">
                                </div>
                                <div class="col-md-4" id="new_millar_bulto_box">
                                    <label class="form-label small text-white-50">Factor Escala (Mil/Bulto)</label>
                                    <input type="number" step="any" name="millar_per_bulto" class="form-control bg-dark border-secondary text-white font-monospace" value="20" id="new_millar_bulto" oninput="recalculateNewProductSimulation()">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-white-50">
                                        Peso Teórico por Millar (PESO) <span class="badge bg-secondary">Auto</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               id="new_theoretical_weight" 
                                               class="form-control bg-secondary text-white border-secondary fw-bold" 
                                               value="0.000" 
                                               step="any" 
                                               readonly>
                                        <span class="input-group-text bg-dark text-white-50 border-secondary">Kg/mil</span>
                                    </div>
                                    <small class="text-white-50" style="font-size: 10px;">Cálculo base: Ancho × Largo × Calibre</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-warning fw-bold">
                                        Peso Real Unidad Venta (PESO_R)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               step="any" 
                                               name="unit_weight_kg" 
                                               class="form-control border-warning text-warning fw-bold bg-dark" 
                                               value="1.000" 
                                               id="new_peso_r" 
                                               oninput="recalculateNewProductSimulation(true)">
                                        <span class="input-group-text bg-dark text-warning border-warning fw-bold">Kg</span>
                                    </div>
                                    <small class="text-white-50" style="font-size: 10px;">PESO × Factor Escala (Editable)</small>
                                </div>
                            </div>

                            <!-- Tarjeta de Costo Materia Prima Calculado -->
                            <div class="p-3 bg-black rounded border border-warning mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-white-50 d-block">Costo Materia Prima Calculado (COSTO = PESO_R × $/KG):</small>
                                        <span class="fs-4 fw-bold text-warning font-monospace" id="new_costo_preview">$0.000</span>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-white-50 d-block">$/KG Aplicado:</small>
                                        <span class="text-success font-monospace fw-bold" id="new_pkg_preview">${{ number_format($settings->resin_price_per_kg, 3) }}/kg</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Márgenes de Referencia Informativos del Excel -->
                            <h6 class="fw-bold text-secondary mb-2 small text-uppercase">Márgenes de Referencia Informativos</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-dark border border-secondary-subtle mb-0 text-center" style="font-size: 11px;">
                                    <thead>
                                        <tr class="bg-black">
                                            <th>40% (x1.40)</th>
                                            <th>45% (x1.45)</th>
                                            <th>50% (x1.50)</th>
                                            <th>60% (x1.65)</th>
                                            <th>2% (x1.73)</th>
                                            <th>1.00 (x2.00)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="font-monospace text-info">
                                            <td id="new_m_40">$0.00</td>
                                            <td id="new_m_45">$0.00</td>
                                            <td id="new_m_50">$0.00</td>
                                            <td id="new_m_60">$0.00</td>
                                            <td id="new_m_2">$0.00</td>
                                            <td id="new_m_1">$0.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Columna Derecha: Calculador Inverso de Precio Fábrica y Utilidad -->
                        <div class="col-lg-6 ps-lg-4">
                            <h6 class="fw-bold text-success mb-3">2. Calculador Inverso de Precio de Fábrica & Metas</h6>

                            <div class="p-3 bg-dark border border-success rounded mb-3">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Meta Producción / Turno (PRODUCCIÓN)</label>
                                        <div class="input-group">
                                            <input type="number" step="any" name="target_units_per_shift" class="form-control text-warning fw-bold fs-5" value="5" id="new_target_units" oninput="recalculateNewFromDailyTarget()" required>
                                            <span class="input-group-text bg-black border-secondary text-white-50" id="new_target_unit_label">BULTO</span>
                                        </div>
                                        <small class="text-white-50" style="font-size: 10px;">Bobinas, Bultos o Millares por turno</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-white-50 fw-bold">Meta Utilidad Deseada ($ USD / Día)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-black border-secondary text-success fw-bold">$</span>
                                            <input type="number" step="any" name="target_daily_profit" class="form-control text-success fw-bold fs-5" value="{{ $settings->daily_profit_target ?: '105.00' }}" id="new_target_profit" oninput="recalculateNewFromDailyTarget()">
                                        </div>
                                        <small class="text-white-50" style="font-size: 10px;">Utilidad diaria objetivo esperada</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-white-50 fw-bold">Precio Salida de Fábrica (FABRICA = COSTO + Utilidad/PRODUCCIÓN)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-black border-secondary text-info fw-bold">$</span>
                                        <input type="number" step="any" name="price" class="form-control text-info fw-bold fs-4" value="0.00" id="new_fabrica_input" oninput="recalculateNewFromFabricaInput()" required>
                                        <span class="input-group-text bg-black border-secondary text-info fw-bold" id="new_fabrica_unit_tag">/ UND</span>
                                    </div>
                                    <small class="text-info" style="font-size: 11px;">
                                        💡 Puedes editar directamente el precio FABRICA o cambiar la Utilidad deseada arriba para simularlo.
                                    </small>
                                </div>

                                <div class="p-2 bg-black rounded border border-secondary text-center mb-2">
                                    <small class="text-white-50">Utilidad Unitaria: <strong class="text-success font-monospace" id="new_unit_profit_preview">$0.00</strong> | Utilidad Diaria Proyectada: <strong class="text-success fs-5 font-monospace" id="new_daily_profit_preview">$0.00</strong></small>
                                </div>
                            </div>

                            <!-- Escala de Precios de Venta Calculada -->
                            <h6 class="fw-bold text-info mb-2 small text-uppercase">3. Escala de Precios de Venta</h6>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="p-2 bg-black rounded border border-success text-center">
                                        <small class="text-white-50 d-block" style="font-size: 10px;">PRECIO I (Distribuidor)</small>
                                        <strong class="text-success font-monospace fs-5" id="new_tier1_preview">$0.00</strong>
                                        <small class="text-white-50 d-block" style="font-size: 9px;">FABRICA × 1.10 (+10%)</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 bg-black rounded border border-success text-center">
                                        <small class="text-white-50 d-block" style="font-size: 10px;">PRECIO II (Mayorista)</small>
                                        <strong class="text-success font-monospace fs-5" id="new_tier2_preview">$0.00</strong>
                                        <small class="text-white-50 d-block" style="font-size: 9px;">FABRICA × 1.17 (+17%)</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 bg-black rounded border border-success text-center">
                                        <small class="text-white-50 d-block" style="font-size: 10px;">PRECIO III (Minorista)</small>
                                        <strong class="text-success font-monospace fs-5" id="new_tier3_preview">$0.00</strong>
                                        <small class="text-white-50 d-block" style="font-size: 9px;">FABRICA × 1.21 (+21%)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-12">
                                    <label class="form-label small text-white-50">Código SKU Único</label>
                                    <input type="text" name="sku" class="form-control font-monospace" placeholder="Ej. BOL-2030-C15" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Crear Ficha Técnica</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
// ==================== INICIALIZACIÓN DE POPOVERS / TOOLTIPS INFORMATIVOS ====================
function initInfoPopovers() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Popover) return;
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(function (popoverTriggerEl) {
        const existing = bootstrap.Popover.getInstance(popoverTriggerEl);
        if (existing) existing.dispose();

        new bootstrap.Popover(popoverTriggerEl, {
            trigger: 'hover focus',
            html: true,
            container: 'body',
            customClass: 'dark-info-popover shadow-lg'
        });
    });
}
document.addEventListener('DOMContentLoaded', initInfoPopovers);

// ==================== ACTUALIZACIÓN REACTIVA GLOBAL SIN RECARGA ====================
document.addEventListener('DOMContentLoaded', function () {
    const globalForm = document.getElementById('globalCostsForm');
    const submitBtn = document.getElementById('globalSubmitBtn');
    const feedbackAlert = document.getElementById('liveFeedbackAlert');
    const feedbackMsg = document.getElementById('liveFeedbackMessage');

    if (globalForm) {
        globalForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Recalculando...';
            }

            const formData = new FormData(globalForm);

            fetch(globalForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.products) {
                    // Actualizar celdas de productos con animación reactiva
                    data.products.forEach(p => {
                        const costCell = document.getElementById('cost_cell_' + p.id);
                        const fabricaCell = document.getElementById('fabrica_cell_' + p.id);
                        const utilCell = document.getElementById('utildia_cell_' + p.id);
                        const tier1Cell = document.getElementById('tier1_cell_' + p.id);
                        const tier2Cell = document.getElementById('tier2_cell_' + p.id);
                        const tier3Cell = document.getElementById('tier3_cell_' + p.id);
                        const pkgCell = document.getElementById('pkg_cell_' + p.id);

                        function applyUpdate(elem, newText) {
                            if (elem && elem.innerText !== newText) {
                                elem.innerText = newText;
                                elem.classList.remove('cell-live-updated');
                                void elem.offsetWidth; // Trigger reflow
                                elem.classList.add('cell-live-updated');
                            }
                        }

                        applyUpdate(costCell, '$' + p.cost);
                        applyUpdate(fabricaCell, '$' + p.factory_price);
                        applyUpdate(utilCell, '+$' + p.daily_profit + '/día');
                        applyUpdate(tier1Cell, '$' + p.tier_1);
                        applyUpdate(tier2Cell, '$' + p.tier_2);
                        applyUpdate(tier3Cell, '$' + p.tier_3);

                        if (!p.has_formula && pkgCell) {
                            applyUpdate(pkgCell, '$' + p.formula_price_kg + '/kg');
                        }
                    });

                    // Mostrar Feedback Toast
                    if (feedbackAlert) {
                        feedbackAlert.style.display = 'flex';
                        if (feedbackMsg) {
                            feedbackMsg.innerText = `¡Guardado en vivo! Se recalcularon los ${data.products.length} productos del catálogo al instante.`;
                        }
                        setTimeout(() => {
                            feedbackAlert.style.display = 'none';
                        }, 5000);
                    }
                }

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '✅ ¡Guardado y Recalculado!';
                    setTimeout(() => {
                        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Guardar y Recalcular';
                    }, 2500);
                }
            })
            .catch(err => {
                console.error(err);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Guardar y Recalcular';
                }
                alert('Ocurrió un error al guardar. Intentando recarga tradicional...');
                globalForm.submit();
            });
        });
    }
});

// ==================== MOTOR DE BÚSQUEDA INTELIGENTE / FUZZY ====================
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('smartProductSearch');
    const clearBtn = document.getElementById('clearSearchBtn');
    const rows = Array.from(document.querySelectorAll('.product-row'));
    const counterElem = document.getElementById('productsCounter');
    const noResultsElem = document.getElementById('noProductsFoundAlert');
    const filterBadge = document.getElementById('filterStatusBadge');
    const totalCount = rows.length;

    function normalizeText(text) {
        if (!text) return '';
        return text.toString()
            .toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]/g, ' ');
    }

    function normalizeCompact(text) {
        if (!text) return '';
        return text.toString()
            .toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]/g, '');
    }

    function executeFilter() {
        const query = searchInput.value.trim().toLowerCase();
        const compactQuery = normalizeCompact(query);
        const queryNumbers = query.replace(/[^0-9]/g, '');
        const queryLetters = query.replace(/[^a-z]/gi, '').toLowerCase();
        const queryTokens = normalizeText(query).split(/\s+/).filter(Boolean);

        let visibleCount = 0;

        rows.forEach(row => {
            const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();

            if (!query) {
                row.style.display = '';
                visibleCount++;
                return;
            }

            const compactTarget = normalizeCompact(rowSearch);
            const targetNumbers = rowSearch.replace(/[^0-9]/g, '');
            const targetTokens = normalizeText(rowSearch).split(/\s+/).filter(Boolean);

            let match = false;

            if (compactQuery && compactTarget.includes(compactQuery)) {
                match = true;
            }

            if (!match && queryNumbers.length >= 3 && targetNumbers.includes(queryNumbers)) {
                if (!queryLetters || compactTarget.includes(queryLetters)) {
                    match = true;
                }
            }

            if (!match && queryTokens.length > 0) {
                const allTokensMatch = queryTokens.every(qToken => {
                    const qCompact = normalizeCompact(qToken);
                    return targetTokens.some(tToken => tToken.includes(qToken)) || compactTarget.includes(qCompact);
                });
                if (allTokensMatch) {
                    match = true;
                }
            }

            if (match) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (counterElem) {
            counterElem.innerText = `Mostrando ${visibleCount} de ${totalCount} fichas`;
        }

        if (noResultsElem) {
            noResultsElem.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        if (clearBtn) {
            clearBtn.style.display = query ? 'inline-block' : 'none';
        }

        if (filterBadge) {
            if (query) {
                filterBadge.className = 'badge bg-warning text-dark py-2 py-md-3 w-100 fw-bold fs-6';
                filterBadge.innerText = `🎯 ${visibleCount} encontrados`;
            } else {
                filterBadge.className = 'badge bg-secondary py-2 py-md-3 w-100 fs-6';
                filterBadge.innerText = `Mostrando Todos (${totalCount})`;
            }
        }
    }

    searchInput.addEventListener('input', executeFilter);
    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        executeFilter();
        searchInput.focus();
    });

    initPresentationPresets();

    const newModalEl = document.getElementById('newProductModal');
    if (newModalEl) {
        newModalEl.addEventListener('show.bs.modal', function () {
            recalculateNewProductSimulation();
        });
    }
});

// ==================== APLICADOR DE PRESETS DE PRESENTACIÓN ====================
function applyPresentationPreset(id, selectElem) {
    const selectedOpt = selectElem.options[selectElem.selectedIndex];
    if (selectedOpt.value === 'CUSTOM') return;

    const unit = selectedOpt.getAttribute('data-unit');
    const factor = parseFloat(selectedOpt.getAttribute('data-factor')) || 1.0;
    const isComposite = selectedOpt.getAttribute('data-composite') === '1';

    const unitInput = document.getElementById('unit_' + id);
    const millarInput = document.getElementById('millar_bulto_' + id);
    const isVarCheckbox = document.getElementById('is_variable_' + id);
    const isCompCheckbox = document.getElementById('is_composite_' + id);

    if (unitInput) unitInput.value = unit;
    if (millarInput) millarInput.value = factor;

    if (unit === 'KG') {
        if (isVarCheckbox && !isVarCheckbox.checked) {
            isVarCheckbox.checked = true;
            toggleBobinaMode(id);
        }
        if (isCompCheckbox) {
            isCompCheckbox.checked = isComposite;
            toggleCompositeMode(id);
        }
    } else {
        if (isVarCheckbox && isVarCheckbox.checked) {
            isVarCheckbox.checked = false;
            toggleBobinaMode(id);
        }
    }

    recalculateProductSimulation(id);
}

function toggleCompositeMode(id) {
    const isCompElem = document.getElementById('is_composite_' + id);
    const isComp = isCompElem ? isCompElem.checked : false;
    const container = document.getElementById('suggested_rolls_container_' + id);
    if (container) {
        container.style.display = isComp ? 'block' : 'none';
    }
}

function applyNewPresentationPreset(selectElem) {
    const selectedOpt = selectElem.options[selectElem.selectedIndex];
    if (selectedOpt.value === 'CUSTOM') return;

    const unit = selectedOpt.getAttribute('data-unit');
    const factor = parseFloat(selectedOpt.getAttribute('data-factor')) || 1.0;

    const unitInput = document.getElementById('new_sale_unit');
    const millarInput = document.getElementById('new_millar_bulto');
    const isVarCheckbox = document.getElementById('new_is_variable');

    if (unitInput) unitInput.value = unit;
    if (millarInput) millarInput.value = factor;

    if (unit === 'KG') {
        if (isVarCheckbox && !isVarCheckbox.checked) {
            isVarCheckbox.checked = true;
            toggleNewBobinaMode();
        }
    } else {
        if (isVarCheckbox && isVarCheckbox.checked) {
            isVarCheckbox.checked = false;
            toggleNewBobinaMode();
        }
    }

    recalculateNewProductSimulation();
}

function initPresentationPresets() {
    document.querySelectorAll('select[id^="preset_"]').forEach(selectElem => {
        const id = selectElem.id.replace('preset_', '');
        if (id === 'new') return;
        const unit = document.getElementById('unit_' + id)?.value;
        const factor = parseFloat(document.getElementById('millar_bulto_' + id)?.value) || 1.0;

        let matched = false;
        for (let i = 0; i < selectElem.options.length; i++) {
            const opt = selectElem.options[i];
            if (opt.value === 'CUSTOM') continue;
            const optUnit = opt.getAttribute('data-unit');
            const optFactor = parseFloat(opt.getAttribute('data-factor'));
            if (optUnit === unit && Math.abs(optFactor - factor) < 0.0001) {
                selectElem.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched) {
            selectElem.value = 'CUSTOM';
        }
    });
}

// ==================== TOGGLE MODO BOBINA / VENTA POR KILO ====================
function toggleBobinaMode(id) {
    const isVar = document.getElementById('is_variable_' + id).checked;
    const compBox = document.getElementById('composite_rolls_box_' + id);
    const dimContainer = document.getElementById('dim_container_' + id);
    const bobinaAlert = document.getElementById('bobina_alert_' + id);
    const unitSelect = document.getElementById('unit_' + id);
    const millarBox = document.getElementById('millar_bulto_box_' + id);
    const targetUnitLabel = document.getElementById('target_unit_label_' + id);
    const fabricaUnitTag = document.getElementById('fabrica_unit_tag_' + id);

    if (isVar) {
        if (compBox) compBox.style.display = 'block';
        if (dimContainer) dimContainer.style.display = 'none';
        if (bobinaAlert) bobinaAlert.style.display = 'block';
        if (unitSelect) unitSelect.value = 'KG';
        if (millarBox) millarBox.style.display = 'none';
        document.getElementById('millar_bulto_' + id).value = '1.000';
        document.getElementById('peso_r_' + id).value = '1.0000';
        if (targetUnitLabel) targetUnitLabel.innerText = 'BOBINA';
        if (fabricaUnitTag) fabricaUnitTag.innerText = '/ KG';
    } else {
        if (compBox) compBox.style.display = 'none';
        const isCompElem = document.getElementById('is_composite_' + id);
        if (isCompElem) isCompElem.checked = false;
        toggleCompositeMode(id);
        if (dimContainer) dimContainer.style.display = 'flex';
        if (bobinaAlert) bobinaAlert.style.display = 'none';
        if (millarBox) millarBox.style.display = 'block';
        if (unitSelect && unitSelect.value === 'KG') unitSelect.value = 'BULTO';
        if (targetUnitLabel) targetUnitLabel.innerText = unitSelect.value;
        if (fabricaUnitTag) fabricaUnitTag.innerText = '/ UND';
    }

    recalculateProductSimulation(id, isVar);
}

function toggleNewBobinaMode() {
    const isVar = document.getElementById('new_is_variable').checked;
    const dimContainer = document.getElementById('new_dim_container');
    const bobinaAlert = document.getElementById('new_bobina_alert');
    const unitSelect = document.getElementById('new_sale_unit');
    const millarBox = document.getElementById('new_millar_bulto_box');
    const targetUnitLabel = document.getElementById('new_target_unit_label');
    const fabricaUnitTag = document.getElementById('new_fabrica_unit_tag');

    if (isVar) {
        if (dimContainer) dimContainer.style.display = 'none';
        if (bobinaAlert) bobinaAlert.style.display = 'block';
        if (unitSelect) unitSelect.value = 'KG';
        if (millarBox) millarBox.style.display = 'none';
        if (document.getElementById('new_millar_bulto')) document.getElementById('new_millar_bulto').value = '1.000';
        if (document.getElementById('new_peso_r')) document.getElementById('new_peso_r').value = '1.000';
        if (targetUnitLabel) targetUnitLabel.innerText = 'BOBINA';
        if (fabricaUnitTag) fabricaUnitTag.innerText = '/ KG';
    } else {
        if (dimContainer) dimContainer.style.display = 'flex';
        if (bobinaAlert) bobinaAlert.style.display = 'none';
        if (millarBox) millarBox.style.display = 'block';
        if (unitSelect && unitSelect.value === 'KG') unitSelect.value = 'BULTO';
        if (targetUnitLabel) targetUnitLabel.innerText = unitSelect ? unitSelect.value : 'BULTO';
        if (fabricaUnitTag) fabricaUnitTag.innerText = '/ UND';
    }

    recalculateNewProductSimulation(isVar);
}

function recalculateNewProductSimulation(manualOverride = false) {
    const isVarSwitch = document.getElementById('new_is_variable');
    const isVar = isVarSwitch ? isVarSwitch.checked : false;
    const fSelect = document.getElementById('new_formula_select');
    let priceKg = {{ (float)$settings->resin_price_per_kg }};
    if (fSelect && fSelect.selectedIndex >= 0) {
        const selectedOpt = fSelect.options[fSelect.selectedIndex];
        priceKg = parseFloat(selectedOpt.getAttribute('data-cost')) || {{ (float)$settings->resin_price_per_kg }};
    }

    let pesoR = 1.0000;

    if (!isVar) {
        const w = parseFloat(document.getElementById('new_width')?.value) || 0;
        const l = parseFloat(document.getElementById('new_length')?.value) || 0;
        const g = parseFloat(document.getElementById('new_gauge')?.value) || 0;
        const mPerB = parseFloat(document.getElementById('new_millar_bulto')?.value) || 1;

        let pesoTeorico = (w > 0 && l > 0 && g > 0) ? (w * l * g) : 0;
        const theoInput = document.getElementById('new_theoretical_weight');
        if (theoInput) {
            theoInput.value = pesoTeorico.toFixed(3);
        }

        pesoR = parseFloat(document.getElementById('new_peso_r')?.value) || 0;

        if (!manualOverride) {
            let factorEscala = (mPerB > 0) ? mPerB : 1.0;
            if (pesoTeorico > 0) {
                pesoR = pesoTeorico * factorEscala;
            } else {
                pesoR = (pesoR > 0) ? pesoR : 1.0000;
            }
            if (document.getElementById('new_peso_r')) {
                document.getElementById('new_peso_r').value = pesoR.toFixed(3);
            }
        }
    } else {
        const theoInput = document.getElementById('new_theoretical_weight');
        if (theoInput) {
            theoInput.value = '1.000';
        }
        pesoR = 1.0000;
        if (document.getElementById('new_peso_r')) {
            document.getElementById('new_peso_r').value = '1.000';
        }
    }

    const costo = pesoR * priceKg;
    if (document.getElementById('new_costo_preview')) {
        document.getElementById('new_costo_preview').innerText = '$' + costo.toFixed(3);
    }
    if (document.getElementById('new_pkg_preview')) {
        document.getElementById('new_pkg_preview').innerText = '$' + priceKg.toFixed(3) + '/kg';
    }

    // Actualizar márgenes de referencia informativos
    if (document.getElementById('new_m_40')) document.getElementById('new_m_40').innerText = '$' + (costo * 1.40).toFixed(2);
    if (document.getElementById('new_m_45')) document.getElementById('new_m_45').innerText = '$' + (costo * 1.45).toFixed(2);
    if (document.getElementById('new_m_50')) document.getElementById('new_m_50').innerText = '$' + (costo * 1.50).toFixed(2);
    if (document.getElementById('new_m_60')) document.getElementById('new_m_60').innerText = '$' + (costo * 1.65).toFixed(2);
    if (document.getElementById('new_m_2')) document.getElementById('new_m_2').innerText = '$' + (costo * 1.73).toFixed(2);
    if (document.getElementById('new_m_1')) document.getElementById('new_m_1').innerText = '$' + (costo * 2.00).toFixed(2);

    const unitInput = document.getElementById('new_sale_unit');
    const targetUnitLabel = document.getElementById('new_target_unit_label');
    if (targetUnitLabel && unitInput) {
        targetUnitLabel.innerText = isVar ? 'BOBINA' : unitInput.value;
    }

    recalculateNewFromDailyTarget();
}

function recalculateNewFromDailyTarget() {
    const costoText = document.getElementById('new_costo_preview')?.innerText.replace('$', '') || '0';
    const costo = parseFloat(costoText) || 0;
    const targetUnits = parseFloat(document.getElementById('new_target_units')?.value) || 1;
    const targetProfit = parseFloat(document.getElementById('new_target_profit')?.value) || 0;

    const fabrica = costo + (targetProfit / targetUnits);
    if (document.getElementById('new_fabrica_input')) {
        document.getElementById('new_fabrica_input').value = fabrica.toFixed(2);
    }

    updateNewTierPreviews(fabrica, costo, targetUnits);
}

function recalculateNewFromFabricaInput() {
    const costoText = document.getElementById('new_costo_preview')?.innerText.replace('$', '') || '0';
    const costo = parseFloat(costoText) || 0;
    const targetUnits = parseFloat(document.getElementById('new_target_units')?.value) || 1;
    const fabrica = parseFloat(document.getElementById('new_fabrica_input')?.value) || 0;

    const dailyProfit = (fabrica - costo) * targetUnits;
    if (document.getElementById('new_target_profit')) {
        document.getElementById('new_target_profit').value = dailyProfit.toFixed(2);
    }

    updateNewTierPreviews(fabrica, costo, targetUnits);
}

function updateNewTierPreviews(fabrica, costo, targetUnits) {
    const unitProfit = fabrica - costo;
    const dailyProfit = unitProfit * targetUnits;

    if (document.getElementById('new_unit_profit_preview')) {
        document.getElementById('new_unit_profit_preview').innerText = '$' + unitProfit.toFixed(2);
    }
    if (document.getElementById('new_daily_profit_preview')) {
        document.getElementById('new_daily_profit_preview').innerText = '$' + dailyProfit.toFixed(2);
    }

    if (document.getElementById('new_tier1_preview')) {
        document.getElementById('new_tier1_preview').innerText = '$' + (fabrica * 1.10).toFixed(2);
    }
    if (document.getElementById('new_tier2_preview')) {
        document.getElementById('new_tier2_preview').innerText = '$' + (fabrica * 1.17).toFixed(2);
    }
    if (document.getElementById('new_tier3_preview')) {
        document.getElementById('new_tier3_preview').innerText = '$' + (fabrica * 1.21).toFixed(2);
    }
}

// ==================== CÁLCULOS DINÁMICOS ====================
function recalculateProductSimulation(id, manualOverride = false) {
    const isVar = document.getElementById('is_variable_' + id) ? document.getElementById('is_variable_' + id).checked : false;
    const fSelect = document.getElementById('formula_select_' + id);
    const selectedOpt = fSelect.options[fSelect.selectedIndex];
    const priceKg = parseFloat(selectedOpt.getAttribute('data-cost')) || {{ (float)$settings->resin_price_per_kg }};

    let pesoR = 1.0000;

    if (!isVar) {
        const w = parseFloat(document.getElementById('width_' + id).value) || 0;
        const l = parseFloat(document.getElementById('length_' + id).value) || 0;
        const g = parseFloat(document.getElementById('gauge_' + id).value) || 0;
        const unit = document.getElementById('unit_' + id).value;
        const mPerB = parseFloat(document.getElementById('millar_bulto_' + id).value) || 1;

        let pesoTeorico = (w > 0 && l > 0 && g > 0) ? (w * l * g) : 0;
        const theoInput = document.getElementById('theoretical_weight_' + id);
        if (theoInput) {
            theoInput.value = pesoTeorico.toFixed(3);
        }

        pesoR = parseFloat(document.getElementById('peso_r_' + id).value) || 0;

        if (!manualOverride) {
            let factorEscala = (mPerB > 0) ? mPerB : 1.0;
            if (pesoTeorico > 0) {
                pesoR = pesoTeorico * factorEscala;
            } else {
                pesoR = (pesoR > 0) ? pesoR : 1.0000;
            }
            document.getElementById('peso_r_' + id).value = pesoR.toFixed(3);
        }
    } else {
        const theoInput = document.getElementById('theoretical_weight_' + id);
        if (theoInput) {
            theoInput.value = '1.000';
        }
        pesoR = 1.0000;
        document.getElementById('peso_r_' + id).value = '1.000';
    }

    const costo = pesoR * priceKg;
    document.getElementById('costo_preview_' + id).innerText = '$' + costo.toFixed(3);
    document.getElementById('pkg_preview_' + id).innerText = '$' + priceKg.toFixed(3) + '/kg';

    // Actualizar márgenes de referencia
    document.getElementById('m_40_' + id).innerText = '$' + (costo * 1.40).toFixed(2);
    document.getElementById('m_45_' + id).innerText = '$' + (costo * 1.45).toFixed(2);
    document.getElementById('m_50_' + id).innerText = '$' + (costo * 1.50).toFixed(2);
    document.getElementById('m_60_' + id).innerText = '$' + (costo * 1.65).toFixed(2);
    document.getElementById('m_2_' + id).innerText = '$' + (costo * 1.73).toFixed(2);
    document.getElementById('m_1_' + id).innerText = '$' + (costo * 2.00).toFixed(2);

    recalculateFromDailyTarget(id);
}

function recalculateFromDailyTarget(id) {
    const costoText = document.getElementById('costo_preview_' + id).innerText.replace('$', '');
    const costo = parseFloat(costoText) || 0;
    const targetUnits = parseFloat(document.getElementById('target_units_' + id).value) || 1;
    const targetProfit = parseFloat(document.getElementById('target_profit_' + id).value) || 0;

    const fabrica = costo + (targetProfit / targetUnits);
    document.getElementById('fabrica_input_' + id).value = fabrica.toFixed(2);

    updateTierPreviews(id, fabrica, costo, targetUnits);
}

function recalculateFromFabricaInput(id) {
    const costoText = document.getElementById('costo_preview_' + id).innerText.replace('$', '');
    const costo = parseFloat(costoText) || 0;
    const targetUnits = parseFloat(document.getElementById('target_units_' + id).value) || 1;
    const fabrica = parseFloat(document.getElementById('fabrica_input_' + id).value) || 0;

    const dailyProfit = (fabrica - costo) * targetUnits;
    document.getElementById('target_profit_' + id).value = dailyProfit.toFixed(2);

    updateTierPreviews(id, fabrica, costo, targetUnits);
}

function updateTierPreviews(id, fabrica, costo, targetUnits) {
    const unitProfit = fabrica - costo;
    const dailyProfit = unitProfit * targetUnits;

    document.getElementById('unit_profit_preview_' + id).innerText = '$' + unitProfit.toFixed(2);
    document.getElementById('daily_profit_preview_' + id).innerText = '$' + dailyProfit.toFixed(2);

    document.getElementById('tier1_preview_' + id).innerText = '$' + (fabrica * 1.10).toFixed(2);
    document.getElementById('tier2_preview_' + id).innerText = '$' + (fabrica * 1.17).toFixed(2);
    document.getElementById('tier3_preview_' + id).innerText = '$' + (fabrica * 1.21).toFixed(2);
}
</script>
@endsection
