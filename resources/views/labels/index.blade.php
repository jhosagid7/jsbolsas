@extends('layouts.app')
@section('title', 'Generador de Etiquetas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">🏷️ Generador de Etiquetas & Impresión Térmica</h3>
        <p class="text-white-50 mb-0">Seleccione productos del catálogo o lotes aprobados en báscula para imprimir etiquetas individuales o generar hojas en PDF.</p>
    </div>
    <div>
        <a href="{{ route('bag-factory.label-audits') }}" class="btn btn-outline-warning fw-bold">
            <i class="bi bi-shield-check me-1"></i> Auditoría Forense de Etiquetas
        </a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form id="labelForm" action="{{ route('labels.generate') }}" method="POST" target="_blank">
    @csrf
    <input type="hidden" name="output" id="formOutput" value="preview">

    <div class="row g-4">
        <!-- Columna Izquierda: Fuentes de Datos (Catálogo y Báscula) -->
        <div class="col-lg-6">
            <div class="card-custom h-100">
                <!-- Pestañas de Selección -->
                <ul class="nav nav-pills mb-3 gap-2" id="sourceTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-white px-3" id="catalog-tab" data-bs-toggle="pill" data-bs-target="#catalog-pane" type="button" role="tab">
                            <i class="bi bi-box-seam me-1 text-info"></i> Catálogo de Bolsas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-white px-3" id="scale-tab" data-bs-toggle="pill" data-bs-target="#scale-pane" type="button" role="tab">
                            <i class="bi bi-speedometer2 me-1 text-warning"></i> Lotes de Báscula ({{ $recentApproved->count() }})
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="sourceTabContent">
                    <!-- Pestaña 1: Catálogo -->
                    <div class="tab-pane fade show active" id="catalog-pane" role="tabpanel">
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-dark border-secondary text-white-50"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchCatalog" class="form-control bg-dark border-secondary text-white" placeholder="Buscar por nombre o SKU...">
                        </div>

                        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                            <table class="table table-dark table-hover align-middle mb-0" id="tableCatalog">
                                <thead>
                                    <tr class="text-secondary small text-uppercase">
                                        <th>Producto</th>
                                        <th>Tipo / Presentación</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $p)
                                        <tr class="catalog-row" data-name="{{ strtolower($p->name) }}" data-sku="{{ strtolower($p->sku ?? '') }}">
                                            <td>
                                                <strong class="text-white d-block">{{ $p->name }}</strong>
                                                <small class="text-white-50 font-monospace">{{ $p->sku ?: 'S/SKU' }}</small>
                                            </td>
                                            <td>
                                                @if($p->is_variable_quantity)
                                                    <span class="badge bg-warning text-dark">Bobina Variable</span>
                                                @else
                                                    <span class="badge bg-info text-dark">1 {{ strtoupper($p->sale_unit ?: 'BULTO') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-info btn-sm fw-bold" 
                                                    onclick="addItem('catalog', {{ $p->id }}, '{{ addslashes($p->name) }}', '{{ $p->sku ?: 'S/SKU' }}', '{{ $p->is_variable_quantity ? 'Bobina Variable' : '1 ' . strtoupper($p->sale_unit ?: 'BULTO') }}')">
                                                    <i class="bi bi-plus-lg"></i> Agregar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-white-50 py-4">No hay productos registrados en el catálogo.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pestaña 2: Báscula / Lotes Aprobados -->
                    <div class="tab-pane fade" id="scale-pane" role="tabpanel">
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-white-50"><i class="bi bi-person-badge"></i></span>
                                    <select id="filterOperatorScale" class="form-select bg-dark border-secondary text-white">
                                        <option value="">👥 Todos los Operarios</option>
                                        @foreach($operators as $op)
                                            <option value="{{ strtolower($op->name) }}">{{ $op->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-white-50"><i class="bi bi-search"></i></span>
                                    <input type="text" id="searchScale" class="form-control bg-dark border-secondary text-white" placeholder="Filtrar por lote, producto o QR...">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                            <table class="table table-dark table-hover align-middle mb-0" id="tableScale">
                                <thead>
                                    <tr class="text-secondary small text-uppercase">
                                        <th>Lote / Producto</th>
                                        <th>Operario / Presentación</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentApproved as $p)
                                        @php
                                            $opName = $p->shift?->user?->name ?? $p->user?->name ?? 'Operario Planta';
                                        @endphp
                                        <tr class="scale-row" data-name="{{ strtolower($p->product->name ?? '') }}" data-operator="{{ strtolower($opName) }}" data-qr="{{ strtolower($p->qr_code ?? '') }}">
                                            <td>
                                                <span class="badge bg-secondary font-monospace">LOTE-{{ $p->bag_shift_id ?? '01' }}</span>
                                                <strong class="text-white d-block mt-1">{{ $p->product->name ?? 'Bolsa' }}</strong>
                                                <small class="text-white-50 font-monospace">{{ $p->qr_code ?: ('PKG-' . $p->id) }}</small>
                                            </td>
                                            <td>
                                                <small class="text-info d-block fw-bold"><i class="bi bi-person me-1"></i>{{ $opName }}</small>
                                                @if($p->product?->is_variable_quantity)
                                                    <strong class="text-success font-monospace">{{ number_format($p->weight, 2) }} Kg</strong>
                                                @else
                                                    <span class="badge bg-secondary text-light">1 {{ strtoupper($p->product?->sale_unit ?: 'BULTO') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-warning btn-sm fw-bold" 
                                                    onclick="addItem('production', {{ $p->id }}, '{{ addslashes($p->product->name ?? 'Bolsa') }} (LOTE-{{ $p->bag_shift_id ?? '01' }})', '{{ $p->qr_code ?: ('PKG-' . $p->id) }}', '{{ $opName }}')">
                                                    <i class="bi bi-plus-lg"></i> Agregar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-white-50 py-4">No hay producciones aprobadas recientemente.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Selección Actual y Opciones de Impresión -->
        <div class="col-lg-6">
            <!-- Nivel de Etiquetado Jerárquico (Ficha Técnica) -->
            <div class="card-custom mb-3">
                <h5 class="fw-bold text-white mb-2">
                    <i class="bi bi-diagram-3-fill text-warning me-2"></i> Jerarquía de Etiquetado
                </h5>
                <p class="text-white-50 small mb-2">Seleccione si desea imprimir los paquetes de millar que van dentro del bulto, la etiqueta master exterior o el kit completo.</p>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="card-template-option w-100 p-2 rounded border border-secondary text-center cursor-pointer">
                            <input type="radio" name="label_scope" value="millar" checked class="form-check-input mb-1">
                            <strong class="d-block text-white small">🏷️ Por Millar (Interior)</strong>
                            <small class="text-white-50 d-block" style="font-size: 11px;">1 Millar por paquete</small>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="card-template-option w-100 p-2 rounded border border-secondary text-center cursor-pointer">
                            <input type="radio" name="label_scope" value="bulto" class="form-check-input mb-1">
                            <strong class="d-block text-white small">📦 Bulto Master</strong>
                            <small class="text-white-50 d-block" style="font-size: 11px;">1 Etiqueta Exterior</small>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="card-template-option w-100 p-2 rounded border border-secondary text-center cursor-pointer">
                            <input type="radio" name="label_scope" value="kit" class="form-check-input mb-1">
                            <strong class="d-block text-white small">📑 Kit Completo</strong>
                            <small class="text-white-50 d-block" style="font-size: 11px;">Bulto + Millares</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Formato y Diseño -->
            <div class="card-custom mb-3">
                <h5 class="fw-bold text-white mb-3">
                    <i class="bi bi-sliders text-info me-2"></i> Formato y Diseño de Salida
                </h5>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="card-template-option w-100 p-3 rounded border border-secondary text-center cursor-pointer">
                            <input type="radio" name="template" value="80mm" checked class="form-check-input mb-2">
                            <strong class="d-block text-white">Rollo 80mm</strong>
                            <small class="text-white-50 d-block">Grande (Zebra / POS-80)</small>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="card-template-option w-100 p-3 rounded border border-secondary text-center cursor-pointer">
                            <input type="radio" name="template" value="58mm" class="form-check-input mb-2">
                            <strong class="d-block text-white">Rollo 58mm</strong>
                            <small class="text-white-50 d-block">Mini / Portátil Bluetooth</small>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="card-template-option w-100 p-3 rounded border border-secondary text-center cursor-pointer">
                            <input type="radio" name="template" value="sheet" class="form-check-input mb-2">
                            <strong class="d-block text-white">Hoja Carta 3x6</strong>
                            <small class="text-white-50 d-block">18 Etiquetas Adhesivas</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Atribución de Lote y Operario (Para Catálogo) -->
            <div class="card-custom mb-3">
                <h5 class="fw-bold text-white mb-2">
                    <i class="bi bi-person-gear text-warning me-2"></i> Atribución de Lote y Operario (Catálogo)
                </h5>
                <p class="text-white-50 small mb-3">Si imprime desde el catálogo genérico, puede personalizar el operario que lo fabricó, la fecha y el lote.</p>
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label text-white-50 small mb-1 fw-bold">Operario Responsable</label>
                        <select name="operator_id" class="form-select form-select-sm bg-dark border-secondary text-white">
                            <option value="">👤 {{ Auth::user()->name ?? 'Planta M&F' }} (Actual)</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id }}">{{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-white-50 small mb-1 fw-bold">Fecha de Producción</label>
                        <input type="date" name="production_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm bg-dark border-secondary text-white">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white-50 small mb-1 fw-bold">Código de Lote</label>
                        <input type="text" name="batch_code" placeholder="LOTE-01" class="form-control form-control-sm bg-dark border-secondary text-white">
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-white mb-0">
                        <i class="bi bi-list-check text-success me-2"></i> Cola de Impresión (<span id="totalItemsCount">0</span>)
                    </h5>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllItems()">
                        <i class="bi bi-trash"></i> Vaciar Cola
                    </button>
                </div>

                <div class="table-responsive mb-3" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-dark table-striped align-middle mb-0" id="selectedTable">
                        <thead>
                            <tr class="text-secondary small text-uppercase">
                                <th>Elemento</th>
                                <th class="text-center" style="width: 110px;">Copias</th>
                                <th class="text-end" style="width: 60px;">Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="selectedItemsBody">
                            <tr id="emptyRow">
                                <td colspan="3" class="text-center text-white-50 py-4">
                                    <i class="bi bi-tag fs-3 d-block mb-1 text-secondary"></i>
                                    No hay elementos agregados. Seleccione productos de la izquierda para comenzar.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-success fw-bold flex-grow-1 py-2" onclick="submitForm('preview')">
                        <i class="bi bi-printer-fill me-1"></i> Previsualizar / Imprimir Térmico
                    </button>
                    <button type="button" class="btn btn-primary fw-bold flex-grow-1 py-2" onclick="submitForm('pdf')">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
    .cursor-pointer {
        cursor: pointer;
    }
    .card-template-option:has(input:checked) {
        border-color: #0284c7 !important;
        background-color: rgba(2, 132, 199, 0.15) !important;
    }
</style>

<script>
    let selectedItems = {};

    function addItem(type, id, name, code, extra) {
        const key = type + '_' + id;
        if (selectedItems[key]) {
            selectedItems[key].qty += 1;
        } else {
            selectedItems[key] = {
                type: type,
                id: id,
                name: name,
                code: code,
                extra: extra,
                qty: 1
            };
        }
        renderSelected();
    }

    function removeItem(key) {
        delete selectedItems[key];
        renderSelected();
    }

    function updateQty(key, delta) {
        if (selectedItems[key]) {
            selectedItems[key].qty = Math.max(1, selectedItems[key].qty + delta);
            renderSelected();
        }
    }

    function setQty(key, val) {
        if (selectedItems[key]) {
            selectedItems[key].qty = Math.max(1, parseInt(val) || 1);
            renderSelected();
        }
    }

    function clearAllItems() {
        selectedItems = {};
        renderSelected();
    }

    function renderSelected() {
        const tbody = document.getElementById('selectedItemsBody');
        const countSpan = document.getElementById('totalItemsCount');
        const keys = Object.keys(selectedItems);
        
        countSpan.innerText = keys.length;

        if (keys.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyRow">
                    <td colspan="3" class="text-center text-white-50 py-4">
                        <i class="bi bi-tag fs-3 d-block mb-1 text-secondary"></i>
                        No hay elementos agregados. Seleccione productos de la izquierda para comenzar.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        let index = 0;
        for (const key of keys) {
            const item = selectedItems[key];
            html += `
                <tr>
                    <td>
                        <strong class="text-white d-block">${item.name}</strong>
                        <small class="text-white-50 font-monospace">${item.code}</small>
                        <span class="badge ${item.type === 'production' ? 'bg-warning text-dark' : 'bg-info text-dark'} ms-1" style="font-size: 10px;">
                            ${item.type === 'production' ? 'Báscula' : 'Catálogo'}
                        </span>
                        <input type="hidden" name="items[${index}][type]" value="${item.type}">
                        <input type="hidden" name="items[${index}][id]" value="${item.id}">
                        <input type="hidden" name="items[${index}][qty]" value="${item.qty}">
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm justify-content-center" style="width: 100px; margin: 0 auto;">
                            <button type="button" class="btn btn-outline-secondary text-white px-2" onclick="updateQty('${key}', -1)">-</button>
                            <input type="number" class="form-control text-center bg-dark text-white border-secondary px-1" value="${item.qty}" min="1" onchange="setQty('${key}', this.value)">
                            <button type="button" class="btn btn-outline-secondary text-white px-2" onclick="updateQty('${key}', 1)">+</button>
                        </div>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem('${key}')">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                </tr>
            `;
            index++;
        }

        tbody.innerHTML = html;
    }

    function submitForm(output) {
        if (Object.keys(selectedItems).length === 0) {
            alert('Debe agregar al menos un producto a la cola de impresión.');
            return;
        }
        document.getElementById('formOutput').value = output;
        document.getElementById('labelForm').submit();
    }

    // Buscador Catálogo
    document.getElementById('searchCatalog').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        document.querySelectorAll('.catalog-row').forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const sku = row.getAttribute('data-sku') || '';
            row.style.display = (name.includes(query) || sku.includes(query)) ? '' : 'none';
        });
    });

    // Filtros Báscula (Texto + Selector de Operario)
    function filterScaleRows() {
        const query = (document.getElementById('searchScale').value || '').toLowerCase();
        const opFilter = (document.getElementById('filterOperatorScale').value || '').toLowerCase();

        document.querySelectorAll('.scale-row').forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const op = row.getAttribute('data-operator') || '';
            const qr = row.getAttribute('data-qr') || '';

            const matchesText = !query || name.includes(query) || op.includes(query) || qr.includes(query);
            const matchesOperator = !opFilter || op.includes(opFilter);

            row.style.display = (matchesText && matchesOperator) ? '' : 'none';
        });
    }

    document.getElementById('searchScale').addEventListener('input', filterScaleRows);
    document.getElementById('filterOperatorScale').addEventListener('change', filterScaleRows);
</script>
@endsection
