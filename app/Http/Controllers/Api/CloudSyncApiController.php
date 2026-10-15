<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CloudSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CloudSyncApiController extends Controller
{
    protected CloudSyncService $syncService;

    public function __construct(CloudSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Valida el token de sincronización en cabecera o request
     */
    protected function validateToken(Request $request): ?JsonResponse
    {
        $incomingToken = $request->header('X-Sync-Token', $request->input('sync_token'));
        $expectedToken = $this->syncService->getSyncToken();

        if (empty($incomingToken) || !hash_equals($expectedToken, (string)$incomingToken)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado: Token de sincronización inválido o ausente.',
            ], 401);
        }

        return null;
    }

    /**
     * Handshake de verificación de conexión y latencia
     */
    public function handshake(Request $request): JsonResponse
    {
        if ($errorResponse = $this->validateToken($request)) {
            return $errorResponse;
        }

        return response()->json([
            'success'     => true,
            'node_role'   => $this->syncService->getNodeRole(),
            'server_time' => now()->toISOString(),
            'app_version' => '2.3.2',
            'message'     => 'Handshake exitoso con nodo ' . $this->syncService->getNodeRole(),
        ]);
    }

    /**
     * Endpoint de subida (Push): Recibe registros modificados y los aplica localmente
     */
    public function push(Request $request): JsonResponse
    {
        if ($errorResponse = $this->validateToken($request)) {
            return $errorResponse;
        }

        $payload = $request->input('payload');
        if (!is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Cuerpo de solicitud inválido: se esperaba un array de deltas en payload.',
            ], 422);
        }

        try {
            $processedCount = $this->syncService->importDeltas($payload);

            return response()->json([
                'success'         => true,
                'processed_count' => $processedCount,
                'message'         => "Se procesaron {$processedCount} registros correctamente.",
                'server_time'     => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en CloudSyncApiController@push: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar registros sincronizados: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint de bajada (Pull): Entrega registros modificados a partir de fecha since
     */
    public function pull(Request $request): JsonResponse
    {
        if ($errorResponse = $this->validateToken($request)) {
            return $errorResponse;
        }

        $sinceStr = $request->input('since');
        $since = null;
        if (!empty($sinceStr)) {
            try {
                $since = Carbon::parse($sinceStr);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de fecha since inválido.',
                ], 422);
            }
        }

        try {
            $deltas = $this->syncService->exportDeltas($since);

            return response()->json([
                'success'     => true,
                'payload'     => $deltas,
                'server_time' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en CloudSyncApiController@pull: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar registros para sincronización: ' . $e->getMessage(),
            ], 500);
        }
    }
}
