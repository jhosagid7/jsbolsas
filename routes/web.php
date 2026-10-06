<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BagFactoryWebController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes - JSBolsas Pro
|--------------------------------------------------------------------------
|
| Rutas web del sistema de gestión y monitoreo de la Fábrica de Bolsas.
|
*/

// Root redirect
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Dashboard
Route::get('/dashboard', [BagFactoryWebController::class, 'dashboard'])
    ->middleware(['auth'])
    ->name('dashboard');
Route::get('/dashboard/live-data', [BagFactoryWebController::class, 'dashboardLiveData'])
    ->middleware(['auth'])
    ->name('dashboard.live_data');

// Protected JSBolsas Pro Routes
Route::middleware('auth')->group(function () {
    Route::get('welcome', function () {
        return redirect()->route('dashboard');
    })->name('welcome');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Scale / Báscula & Auditoría
    Route::get('/scale', [BagFactoryWebController::class, 'scaleAudit'])->name('scale.index');
    Route::get('/bascula', [BagFactoryWebController::class, 'scaleAudit'])->name('scale.index_alias');
    Route::post('/scale/approve/{id}', [BagFactoryWebController::class, 'approve'])->name('approve');
    Route::post('/scale/bulk-approve', [BagFactoryWebController::class, 'bulkApprove'])->name('bulk_approve');
    Route::post('/scale/adjust/{id}', [BagFactoryWebController::class, 'adjust'])->name('adjust');
    Route::post('/scale/reject/{id}', [BagFactoryWebController::class, 'reject'])->name('reject');

    // Reportes de Producción
    Route::get('/reports-fabrica', [BagFactoryWebController::class, 'reportsIndex'])->name('reports.index');
    Route::get('/reports', [BagFactoryWebController::class, 'reportsIndex'])->name('reports.index_en');
    Route::get('/reportes', [BagFactoryWebController::class, 'reportsIndex'])->name('reports.index_es');
    Route::get('/reports-fabrica/pdf', [BagFactoryWebController::class, 'reportsPdf'])->name('reports.pdf');

    // Fórmulas y Recetas
    Route::get('/formulas', [BagFactoryWebController::class, 'formulasIndex'])->name('formulas.index');
    Route::post('/formulas', [BagFactoryWebController::class, 'formulasStore'])->name('formulas.store');
    Route::post('/formulas/{id}/version', [BagFactoryWebController::class, 'formulasNewVersion'])->name('formulas.new_version');

    // Materias Primas
    Route::get('/raw-materials', [BagFactoryWebController::class, 'rawMaterialsIndex'])->name('raw_materials.index');
    Route::get('/materias-primas', [BagFactoryWebController::class, 'rawMaterialsIndex'])->name('raw_materials.index_alias');
    Route::post('/raw-materials', [BagFactoryWebController::class, 'rawMaterialsStore'])->name('raw_materials.store');
    Route::post('/raw-materials/{id}/price', [BagFactoryWebController::class, 'rawMaterialsUpdatePrice'])->name('raw_materials.update_price');

    // Costos y Simulador de Precios
    Route::get('/costs', [BagFactoryWebController::class, 'costsIndex'])->name('costs.index');
    Route::get('/costos', [BagFactoryWebController::class, 'costsIndex'])->name('costs.index_alias');
    Route::match(['post', 'put'], '/costs', [BagFactoryWebController::class, 'costsUpdate'])->name('costs.update');
    Route::match(['post', 'put'], '/products/{id}/technical', [BagFactoryWebController::class, 'productsTechnicalUpdate'])->name('products.technical.update');

    // Catálogo de Bolsas y Fichas Técnicas
    Route::get('/catalogo-bolsas', [BagFactoryWebController::class, 'productsIndex'])->name('products.index');
    Route::get('/catalogo', [BagFactoryWebController::class, 'productsIndex'])->name('products.index_alias');
    Route::post('/catalogo-bolsas', [BagFactoryWebController::class, 'productsStore'])->name('products.store');
    Route::put('/catalogo-bolsas/{id}', [BagFactoryWebController::class, 'productsUpdate'])->name('products.update');
    Route::delete('/catalogo-bolsas/{id}', [BagFactoryWebController::class, 'productsDestroy'])->name('products.destroy');

    // Usuarios y Roles de Fábrica
    Route::get('/usuarios-fabrica', [BagFactoryWebController::class, 'usersIndex'])->name('users.index');
    Route::get('/usuarios', [BagFactoryWebController::class, 'usersIndex'])->name('users.index_alias');
    Route::post('/usuarios-fabrica', [BagFactoryWebController::class, 'usersStore'])->name('users.store');
    Route::put('/usuarios-fabrica/{id}', [BagFactoryWebController::class, 'usersUpdate'])->name('users.update');
    Route::delete('/usuarios-fabrica/{id}', [BagFactoryWebController::class, 'usersDestroy'])->name('users.destroy');

    // Máquinas y Líneas
    Route::get('/machines', [BagFactoryWebController::class, 'machinesIndex'])->name('machines.index');
    Route::get('/maquinas', [BagFactoryWebController::class, 'machinesIndex'])->name('machines.index_alias');
    Route::get('/machines/{id}', [BagFactoryWebController::class, 'machinesShow'])->name('machines.show');
    Route::post('/machines', [BagFactoryWebController::class, 'machinesStore'])->name('machines.store');
    Route::delete('/machines/{id}', [BagFactoryWebController::class, 'machinesDestroy'])->name('machines.destroy');
    Route::post('/machines/{id}/incidents', [BagFactoryWebController::class, 'machinesReportIncident'])->name('machines.incidents.store');
    Route::put('/machines/{id}/incidents/{incidentId}/resolve', [BagFactoryWebController::class, 'machinesResolveIncident'])->name('machines.incidents.resolve');

    // Ticket térmico de pesaje y etiquetas
    Route::get('/ticket/{id}', [BagFactoryWebController::class, 'ticket'])->name('ticket');
    Route::get('/ticket/{id}/pdf', [BagFactoryWebController::class, 'ticketPdf'])->name('ticket.pdf');
    Route::get('/ticket/shift/{shift_id}', [BagFactoryWebController::class, 'printShiftLabels'])->name('ticket.shift');
    Route::get('/ticket/shift/{shift_id}/pdf', [BagFactoryWebController::class, 'printShiftLabelsPdf'])->name('ticket.shift.pdf');
    Route::get('/ticket/batch/print', [BagFactoryWebController::class, 'printBatchLabels'])->name('ticket.batch');
    Route::get('/ticket/batch/pdf', [BagFactoryWebController::class, 'printBatchLabelsPdf'])->name('ticket.batch.pdf');

    // Módulo Generador de Etiquetas & Auditoría Forense
    Route::get('/etiquetas', [\App\Http\Controllers\LabelGeneratorController::class, 'index'])->name('labels.index');
    Route::get('/labels', [\App\Http\Controllers\LabelGeneratorController::class, 'index'])->name('labels.index_en');
    Route::post('/etiquetas/generate', [\App\Http\Controllers\LabelGeneratorController::class, 'generate'])->name('labels.generate');
    Route::post('/etiquetas/confirm-print', [\App\Http\Controllers\LabelGeneratorController::class, 'confirmPrint'])->name('labels.confirm_print');
    Route::get('/etiquetas/direct-pdf', [\App\Http\Controllers\LabelGeneratorController::class, 'directPdf'])->name('labels.pdf.direct');
    Route::get('/auditoria-etiquetas', [BagFactoryWebController::class, 'labelAudits'])->name('bag-factory.label-audits');
    Route::get('/labels/audits', [BagFactoryWebController::class, 'labelAudits'])->name('bag-factory.label-audits-en');

    // Nómina y Rendimiento por Metas de Operarios
    Route::get('/bag-factory/payroll', [BagFactoryWebController::class, 'payrollIndex'])->name('bag-factory.payroll');
    Route::get('/nomina', [BagFactoryWebController::class, 'payrollIndex'])->name('bag_factory.payroll');

    // Estación Web de Operarios (Carga Rápida & Pesaje por Lotes)
    Route::get('/operario/estacion', [BagFactoryWebController::class, 'operatorStation'])->name('operator.station');
    Route::post('/operario/produccion/batch', [BagFactoryWebController::class, 'operatorStoreBatch'])->name('operator.store_batch');
    Route::put('/operario/produccion/batch/{id}', [BagFactoryWebController::class, 'operatorUpdateBatch'])->name('operator.update_batch');
    Route::delete('/operario/produccion/batch/{id}', [BagFactoryWebController::class, 'operatorDestroyBatch'])->name('operator.destroy_batch');
    Route::post('/operario/turno/abrir', [BagFactoryWebController::class, 'operatorOpenShift'])->name('operator.open_shift');
    Route::post('/operario/turno/cerrar', [BagFactoryWebController::class, 'operatorCloseShift'])->name('operator.close_shift');
});

require __DIR__ . '/auth.php';
