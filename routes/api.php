<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BagFactoryApiController;
use App\Http\Controllers\Api\BagsProductionApiController;

/*
|--------------------------------------------------------------------------
| API Routes - JSBolsas Pro
|--------------------------------------------------------------------------
|
| Endpoints para la aplicación móvil Flutter y servicios de Fábrica de Bolsas.
|
*/

// Auth Endpoint
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // Mobile App Bolsas compatibility
    Route::prefix('bolsas')->group(function () {
        Route::get('/products', [BagsProductionApiController::class, 'products']);
        Route::post('/production', [BagsProductionApiController::class, 'store']);
        Route::get('/production/history', [BagsProductionApiController::class, 'history']);
    });

    // Bag Factory Dedicated Mobile & Synchronization API
    Route::prefix('bag-factory')->group(function () {
        Route::get('/products', [BagFactoryApiController::class, 'products']);
        Route::get('/machines', [BagFactoryApiController::class, 'machines']);
        Route::post('/shifts/open', [BagFactoryApiController::class, 'openShift']);
        Route::post('/shifts/close', [BagFactoryApiController::class, 'closeShift']);
        Route::get('/shifts/active', [BagFactoryApiController::class, 'activeShift']);
        Route::get('/shifts/history', [BagFactoryApiController::class, 'shiftsHistory']);
        Route::post('/productions/sync', [BagFactoryApiController::class, 'syncProductions']);

        // Supervisor & Operations Manager Endpoints
        Route::get('/supervisor/feed', [BagFactoryApiController::class, 'supervisorFeed']);
        Route::put('/supervisor/productions/{id}', [BagFactoryApiController::class, 'adjustProduction']);
        Route::post('/supervisor/productions/{id}/approve', [BagFactoryApiController::class, 'approveProduction']);
        Route::post('/supervisor/productions/bulk-approve', [BagFactoryApiController::class, 'bulkApprove']);
        Route::post('/supervisor/productions/{id}/reject', [BagFactoryApiController::class, 'rejectProduction']);
        Route::get('/supervisor/pre-stock', [BagFactoryApiController::class, 'preStock']);
        Route::get('/supervisor/ticket/{id}', [BagFactoryApiController::class, 'ticketData']);
        Route::get('/ticket/{id}', [BagFactoryApiController::class, 'ticketData']);

        // Warehouse Pre-stock Lifting & Receiving Endpoints
        Route::get('/lifting/pending', [BagFactoryApiController::class, 'liftingPending']);
        Route::get('/lifting/scan/{code}', [BagFactoryApiController::class, 'scanQr']);
        Route::post('/lifting/receive', [BagFactoryApiController::class, 'receiveLifting']);
    });
});
