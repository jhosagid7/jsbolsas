<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\BagShift;
use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\RawMaterial;
use App\Models\RawMaterialStock;
use App\Models\RawMaterialTransfer;
use App\Models\ProductionFormula;
use App\Models\FormulaVersion;
use App\Models\FormulaItem;
use App\Models\CloudSyncLog;

class CloudSyncService
{
    protected string $cloudUrl;
    protected string $syncToken;
    protected string $nodeRole;

    public function __construct()
    {
        $this->cloudUrl = rtrim(config('services.cloud_sync.url', env('CLOUD_SYNC_URL', 'https://bolsas.plasticosmyf.com')), '/');
        $this->syncToken = config('services.cloud_sync.token', env('CLOUD_SYNC_TOKEN', 'jsbolsas_secure_sync_token_2026'));
        $this->nodeRole = env('NODE_ROLE', 'local');
    }

    public function getCloudUrl(): string
    {
        return $this->cloudUrl;
    }

    public function getNodeRole(): string
    {
        return $this->nodeRole;
    }

    public function getSyncToken(): string
    {
        return $this->syncToken;
    }

    /**
     * Prueba conectividad y latencia contra el endpoint de Handshake
     */
    public function pingCloud(): array
    {
        $start = microtime(true);
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Sync-Token' => $this->syncToken])
                ->post("{$this->cloudUrl}/api/cloud-sync/handshake", [
                    'node_role'  => $this->nodeRole,
                    'timestamp'  => now()->toISOString(),
                    'app_version'=> '2.3.2',
                ]);

            $latency = round((microtime(true) - $start) * 1000, 1);

            if ($response->successful()) {
                return [
                    'connected'  => true,
                    'latency_ms' => $latency,
                    'message'    => 'Conexión exitosa con VPS en la nube',
                    'server_time'=> $response->json('server_time'),
                ];
            }

            return [
                'connected'  => false,
                'latency_ms' => $latency,
                'message'    => 'VPS respondió con error: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'connected'  => false,
                'latency_ms' => 0,
                'message'    => 'Sin conexión a internet o VPS inalcanzable: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtiene la última fecha/hora de sincronización exitosa
     */
    public function getLastSyncTime(): ?Carbon
    {
        $lastLog = CloudSyncLog::where('status', 'success')->latest('synced_at')->first();
        return $lastLog ? Carbon::parse($lastLog->synced_at) : null;
    }

    /**
     * Calcula la cantidad de registros locales pendientes por subir
     */
    public function getPendingCount(): int
    {
        // En el nodo Cloud (VPS central), el servidor es el receptor de las plantas.
        // No tiene registros pendientes por subir a nadie; está en la nube.
        if ($this->nodeRole === 'cloud') {
            return 0;
        }

        $lastSync = $this->getLastSyncTime();
        $dateThreshold = $lastSync ?: now()->subDays(30);

        $pendingProds = BagProduction::where('updated_at', '>', $dateThreshold)->count();
        $pendingShifts = BagShift::where('updated_at', '>', $dateThreshold)->count();
        $pendingTransfers = RawMaterialTransfer::where('updated_at', '>', $dateThreshold)->count();
        $pendingRaw = RawMaterial::where('updated_at', '>', $dateThreshold)->count();
        $pendingFormulas = ProductionFormula::where('updated_at', '>', $dateThreshold)->count();
        $pendingProducts = BagProduct::where('updated_at', '>', $dateThreshold)->count();

        return $pendingProds + $pendingShifts + $pendingTransfers + $pendingRaw + $pendingFormulas + $pendingProducts;
    }

    /**
     * Empaqueta registros modificados desde una fecha específica
     */
    public function exportDeltas(?Carbon $since = null): array
    {
        $queryDate = $since ?: now()->subDays(30);

        // 1. Turnos operativos
        $shifts = BagShift::with(['user', 'machine'])
            ->where('updated_at', '>=', $queryDate)
            ->get()
            ->map(function ($s) {
                return [
                    'sync_id'        => $s->sync_id ?: ('SHIFT-' . $s->id),
                    'user_email'     => $s->user?->email,
                    'machine_code'   => $s->machine?->code,
                    'shift_type'     => $s->shift_type,
                    'start_time'     => $s->start_time ? $s->start_time->toISOString() : null,
                    'end_time'       => $s->end_time ? $s->end_time->toISOString() : null,
                    'status'         => $s->status,
                    'total_packages' => (float)$s->total_packages,
                    'total_weight'   => (float)$s->total_weight,
                    'notes'          => $s->notes,
                    'updated_at'     => $s->updated_at ? $s->updated_at->toISOString() : null,
                ];
            })->toArray();

        // 2. Producciones y pesajes (bobinas, bultos, mermas)
        $productions = BagProduction::with(['user', 'product', 'machine', 'shift', 'formula'])
            ->where('updated_at', '>=', $queryDate)
            ->get()
            ->map(function ($p) {
                return [
                    'sync_id'                  => $p->sync_id ?: ('PROD-' . $p->id),
                    'shift_sync_id'            => $p->shift?->sync_id,
                    'product_sku'              => $p->product?->sku,
                    'machine_code'             => $p->machine?->code,
                    'user_email'               => $p->user?->email,
                    'formula_code'             => $p->formula?->code,
                    'quantity'                 => (float)$p->quantity,
                    'weight'                   => (float)$p->weight,
                    'original_weight'          => (float)$p->original_weight,
                    'waste_weight_kg'          => (float)$p->waste_weight_kg,
                    'is_waste_record'          => (bool)$p->is_waste_record,
                    'recorded_at'              => $p->recorded_at ? $p->recorded_at->toISOString() : null,
                    'status'                   => $p->status,
                    'is_printed'               => (bool)$p->is_printed,
                    'weight_quality_grade'     => $p->weight_quality_grade,
                    'weight_deviation_percent' => (float)$p->weight_deviation_percent,
                    'completed_packages_count' => (float)$p->completed_packages_count,
                    'fractional_units'         => (float)$p->fractional_units,
                    'is_package_completed'     => (bool)$p->is_package_completed,
                    'labor_earned_amount'      => (float)$p->labor_earned_amount,
                    'labor_retained_amount'    => (float)$p->labor_retained_amount,
                    'qr_code'                  => $p->qr_code,
                    'metadata'                 => $p->metadata,
                    'updated_at'               => $p->updated_at ? $p->updated_at->toISOString() : null,
                ];
            })->toArray();

        // 3. Materias Primas y Stocks
        $rawMaterials = RawMaterial::where('updated_at', '>=', $queryDate)->get()->toArray();
        
        $stocks = RawMaterialStock::with('rawMaterial')
            ->where('updated_at', '>=', $queryDate)
            ->get()
            ->map(function ($st) {
                return [
                    'raw_material_code' => $st->rawMaterial?->code,
                    'warehouse_type'    => $st->warehouse_type,
                    'sacos_count'       => (float)$st->sacos_count,
                    'total_kg'          => (float)$st->total_kg,
                    'updated_at'        => $st->updated_at ? $st->updated_at->toISOString() : null,
                ];
            })->toArray();

        // 4. Traslados entre depósitos
        $transfers = RawMaterialTransfer::with(['rawMaterial', 'transferredByUser'])
            ->where('updated_at', '>=', $queryDate)
            ->get()
            ->map(function ($tr) {
                return [
                    'sync_id'               => $tr->sync_id ?: ('TRANS-' . $tr->id),
                    'raw_material_code'     => $tr->rawMaterial?->code,
                    'from_warehouse'        => $tr->from_warehouse,
                    'to_warehouse'          => $tr->to_warehouse,
                    'sacos_transferred'     => (float)$tr->sacos_transferred,
                    'weight_kg'             => (float)$tr->weight_kg,
                    'transferred_by_email'  => $tr->transferredByUser?->email,
                    'transferred_at'        => $tr->transferred_at ? $tr->transferred_at->toISOString() : null,
                    'notes'                 => $tr->notes,
                    'updated_at'            => $tr->updated_at ? $tr->updated_at->toISOString() : null,
                ];
            })->toArray();

        // 5. Fórmulas de Mezcla (con receta activa e ingredientes)
        $formulas = ProductionFormula::with(['currentVersion.items.rawMaterial'])
            ->where('updated_at', '>=', $queryDate)
            ->get()
            ->map(function ($f) {
                $items = [];
                if ($f->currentVersion) {
                    foreach ($f->currentVersion->items as $it) {
                        $items[] = [
                            'raw_material_code' => $it->rawMaterial?->code,
                            'quantity_kg'       => (float)$it->quantity_kg,
                            'cost_per_kg'       => (float)$it->cost_per_kg,
                        ];
                    }
                }
                return [
                    'code'        => $f->code ?: ('FORM-' . $f->id),
                    'name'        => $f->name,
                    'description' => $f->description,
                    'is_active'   => (bool)$f->is_active,
                    'items'       => $items,
                    'updated_at'  => $f->updated_at ? $f->updated_at->toISOString() : null,
                ];
            })->toArray();

        // 6. Catálogo de Productos / Medidas de Bolsas y Bobinas
        $products = BagProduct::where('updated_at', '>=', $queryDate)
            ->get()
            ->map(function ($pr) {
                return [
                    'sku'                    => $pr->sku,
                    'name'                   => $pr->name,
                    'category'               => $pr->category,
                    'material_type'          => $pr->material_type,
                    'width_inch'             => (float)$pr->width_inch,
                    'length_inch'            => (float)$pr->length_inch,
                    'gusset_cm'              => (float)$pr->gusset_cm,
                    'gauge_caliber'          => (float)$pr->gauge_caliber,
                    'unit_weight_kg'         => (float)$pr->unit_weight_kg,
                    'millar_per_bulto'       => (float)$pr->millar_per_bulto,
                    'sale_unit'              => $pr->sale_unit,
                    'is_variable_quantity'   => (bool)$pr->is_variable_quantity,
                    'target_units_per_shift' => (int)$pr->target_units_per_shift,
                    'is_active'              => (bool)$pr->is_active,
                    'price'                  => (float)$pr->price,
                    'cost'                   => (float)$pr->cost,
                    'updated_at'             => $pr->updated_at ? $pr->updated_at->toISOString() : null,
                ];
            })->toArray();

        // 8. Máquinas y Líneas de Producción
        $machines = BagMachine::where('updated_at', '>=', $queryDate)
            ->get()
            ->map(function ($m) {
                return [
                    'name'       => $m->name,
                    'code'       => $m->code,
                    'type'       => $m->type,
                    'is_active'  => (bool)$m->is_active,
                    'updated_at' => $m->updated_at ? $m->updated_at->toISOString() : null,
                ];
            })->toArray();

        return [
            'machines'      => $machines,
            'shifts'        => $shifts,
            'productions'   => $productions,
            'raw_materials' => $rawMaterials,
            'stocks'        => $stocks,
            'transfers'     => $transfers,
            'formulas'      => $formulas,
            'products'      => $products,
        ];
    }

    /**
     * Importa registros recibidos aplicando resolución de conflictos por sync_id / código
     */
    public function importDeltas(array $payload): int
    {
        $processed = 0;

        DB::beginTransaction();
        try {
            // 0. Máquinas y Líneas de Producción
            if (!empty($payload['machines'])) {
                foreach ($payload['machines'] as $mData) {
                    if (empty($mData['code'])) continue;
                    BagMachine::updateOrCreate(
                        ['code' => $mData['code']],
                        [
                            'name'      => $mData['name'] ?? $mData['code'],
                            'type'      => in_array($mData['type'] ?? '', ['extrusora', 'selladora']) ? $mData['type'] : 'selladora',
                            'is_active' => (bool)($mData['is_active'] ?? true),
                        ]
                    );
                    $processed++;
                }
            }

            // 1. Materias Primas
            if (!empty($payload['raw_materials'])) {
                foreach ($payload['raw_materials'] as $matData) {
                    if (empty($matData['code'])) continue;
                    RawMaterial::updateOrCreate(
                        ['code' => $matData['code']],
                        [
                            'name'                   => $matData['name'] ?? $matData['code'],
                            'cost_per_kg'            => (float)($matData['cost_per_kg'] ?? 0),
                            'shipping_cost_per_kg'   => (float)($matData['shipping_cost_per_kg'] ?? 0),
                            'customs_cost_per_kg'    => (float)($matData['customs_cost_per_kg'] ?? 0),
                            'local_freight_per_kg'   => (float)($matData['local_freight_per_kg'] ?? 0),
                            'is_active'              => (bool)($matData['is_active'] ?? true),
                        ]
                    );
                    $processed++;
                }
            }

            // 2. Existencias de Materias Primas por Depósito
            if (!empty($payload['stocks'])) {
                foreach ($payload['stocks'] as $stData) {
                    $mat = RawMaterial::where('code', $stData['raw_material_code'])->first();
                    if (!$mat) continue;

                    RawMaterialStock::updateOrCreate(
                        ['raw_material_id' => $mat->id, 'warehouse_type' => $stData['warehouse_type']],
                        [
                            'sacos_count' => (float)$stData['sacos_count'],
                            'total_kg'    => (float)$stData['total_kg'],
                        ]
                    );
                    $processed++;
                }
            }

            // 3. Catálogo de Productos
            if (!empty($payload['products'])) {
                foreach ($payload['products'] as $prodData) {
                    if (empty($prodData['sku'])) continue;
                    BagProduct::updateOrCreate(
                        ['sku' => $prodData['sku']],
                        [
                            'name'                   => $prodData['name'] ?? $prodData['sku'],
                            'category'               => $prodData['category'] ?? 'General',
                            'material_type'          => $prodData['material_type'] ?? 'Baja Densidad',
                            'width_inch'             => (float)($prodData['width_inch'] ?? 0),
                            'length_inch'            => (float)($prodData['length_inch'] ?? 0),
                            'gusset_cm'              => (float)($prodData['gusset_cm'] ?? 0),
                            'gauge_caliber'          => (float)($prodData['gauge_caliber'] ?? 0),
                            'unit_weight_kg'         => (float)($prodData['unit_weight_kg'] ?? 1),
                            'millar_per_bulto'       => (float)($prodData['millar_per_bulto'] ?? 1),
                            'sale_unit'              => $prodData['sale_unit'] ?? 'MILLAR',
                            'is_variable_quantity'   => (bool)($prodData['is_variable_quantity'] ?? false),
                            'target_units_per_shift' => (int)($prodData['target_units_per_shift'] ?? 5),
                            'is_active'              => (bool)($prodData['is_active'] ?? true),
                            'price'                  => (float)($prodData['price'] ?? 0),
                            'cost'                   => (float)($prodData['cost'] ?? 0),
                        ]
                    );
                    $processed++;
                }
            }

            // 4. Fórmulas de Mezcla
            if (!empty($payload['formulas'])) {
                foreach ($payload['formulas'] as $fData) {
                    if (empty($fData['code'])) continue;
                    $formula = ProductionFormula::firstOrCreate(
                        ['code' => $fData['code']],
                        [
                            'name'        => $fData['name'] ?? $fData['code'],
                            'description' => $fData['description'] ?? '',
                            'is_active'   => (bool)($fData['is_active'] ?? true),
                        ]
                    );

                    // Si trae items de versión
                    if (!empty($fData['items']) && is_array($fData['items'])) {
                        $itemsPayload = [];
                        foreach ($fData['items'] as $item) {
                            $rm = RawMaterial::where('code', $item['raw_material_code'])->first();
                            if ($rm) {
                                $itemsPayload[] = [
                                    'raw_material_id' => $rm->id,
                                    'quantity_kg'     => (float)$item['quantity_kg'],
                                ];
                            }
                        }
                        if (!empty($itemsPayload) && !$formula->current_version_id) {
                            $formula->createNewVersion($itemsPayload, null, 'Sincronizado vía CloudSync');
                        }
                    }
                    $processed++;
                }
            }

            // 5. Turnos de Operarios
            if (!empty($payload['shifts'])) {
                foreach ($payload['shifts'] as $sData) {
                    if (empty($sData['sync_id'])) continue;

                    BagShift::updateOrCreate(
                        ['sync_id' => $sData['sync_id']],
                        [
                            'user_id'        => $this->resolveUserId($sData['user_email'] ?? null),
                            'machine_id'     => $this->resolveMachineId($sData['machine_code'] ?? null),
                            'shift_type'     => in_array($sData['shift_type'] ?? '', ['diurno', 'nocturno']) ? $sData['shift_type'] : 'diurno',
                            'start_time'     => $sData['start_time'] ? Carbon::parse($sData['start_time']) : now(),
                            'end_time'       => $sData['end_time'] ? Carbon::parse($sData['end_time']) : null,
                            'status'         => in_array($sData['status'] ?? '', ['open', 'closed']) ? $sData['status'] : 'open',
                            'total_packages' => (float)($sData['total_packages'] ?? 0),
                            'total_weight'   => (float)($sData['total_weight'] ?? 0),
                            'notes'          => $sData['notes'] ?? null,
                        ]
                    );
                    $processed++;
                }
            }

            // 6. Producciones (Bobinas, Bultos, Mermas)
            if (!empty($payload['productions'])) {
                foreach ($payload['productions'] as $pData) {
                    if (empty($pData['sync_id'])) continue;

                    $pShift = !empty($pData['shift_sync_id']) ? BagShift::where('sync_id', $pData['shift_sync_id'])->first() : null;
                    $pForm = !empty($pData['formula_code']) ? ProductionFormula::where('code', $pData['formula_code'])->first() : null;

                    BagProduction::updateOrCreate(
                        ['sync_id' => $pData['sync_id']],
                        [
                            'bag_shift_id'             => $pShift?->id ?: (BagShift::latest('id')->value('id') ?: 1),
                            'product_id'               => $this->resolveProductId($pData['product_sku'] ?? null),
                            'machine_id'               => $this->resolveMachineId($pData['machine_code'] ?? null),
                            'user_id'                  => $this->resolveUserId($pData['user_email'] ?? null),
                            'production_formula_id'    => $pForm?->id,
                            'quantity'                 => (float)($pData['quantity'] ?? 1),
                            'weight'                   => (float)($pData['weight'] ?? 0),
                            'original_weight'          => (float)($pData['original_weight'] ?? $pData['weight'] ?? 0),
                            'waste_weight_kg'          => (float)($pData['waste_weight_kg'] ?? 0),
                            'is_waste_record'          => (bool)($pData['is_waste_record'] ?? false),
                            'recorded_at'              => $pData['recorded_at'] ? Carbon::parse($pData['recorded_at']) : now(),
                            'status'                   => in_array($pData['status'] ?? '', ['pending_review', 'approved', 'rejected']) ? $pData['status'] : 'pending_review',
                            'is_printed'               => (bool)($pData['is_printed'] ?? false),
                            'weight_quality_grade'     => $pData['weight_quality_grade'] ?? 'B',
                            'weight_deviation_percent' => (float)($pData['weight_deviation_percent'] ?? 0),
                            'completed_packages_count' => (float)($pData['completed_packages_count'] ?? 1),
                            'fractional_units'         => (float)($pData['fractional_units'] ?? 0),
                            'is_package_completed'     => (bool)($pData['is_package_completed'] ?? true),
                            'labor_earned_amount'      => (float)($pData['labor_earned_amount'] ?? 0),
                            'labor_retained_amount'    => (float)($pData['labor_retained_amount'] ?? 0),
                            'qr_code'                  => $pData['qr_code'] ?? ('PKG-' . strtoupper(Str::random(10))),
                            'metadata'                 => $pData['metadata'] ?? [],
                        ]
                    );
                    $processed++;
                }
            }

            // 7. Traslados de Materia Prima
            if (!empty($payload['transfers'])) {
                foreach ($payload['transfers'] as $trData) {
                    if (empty($trData['sync_id'])) continue;
                    $trMat = RawMaterial::where('code', $trData['raw_material_code'])->first();
                    $trUser = !empty($trData['transferred_by_email']) ? User::where('email', $trData['transferred_by_email'])->first() : null;

                    if ($trMat) {
                        RawMaterialTransfer::updateOrCreate(
                            ['sync_id' => $trData['sync_id']],
                            [
                                'raw_material_id'   => $trMat->id,
                                'from_warehouse'    => $trData['from_warehouse'] ?? 'principal',
                                'to_warehouse'      => $trData['to_warehouse'] ?? 'planta',
                                'sacos_transferred' => (float)($trData['sacos_transferred'] ?? 0),
                                'weight_kg'         => (float)($trData['weight_kg'] ?? 0),
                                'transferred_by'    => !empty($trData['transferred_by_email']) ? $this->resolveUserId($trData['transferred_by_email']) : null,
                                'transferred_at'    => $trData['transferred_at'] ? Carbon::parse($trData['transferred_at']) : now(),
                                'notes'             => $trData['notes'] ?? null,
                            ]
                        );
                        $processed++;
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al importar deltas en CloudSyncService: ' . $e->getMessage());
            throw $e;
        }

        return $processed;
    }

    /**
     * Sube los deltas locales al VPS
     */
    public function pushDeltas(?Carbon $since = null): array
    {
        $payload = $this->exportDeltas($since ?: $this->getLastSyncTime());
        $totalItems = count($payload['machines'] ?? [])
            + count($payload['shifts'] ?? [])
            + count($payload['productions'] ?? [])
            + count($payload['raw_materials'] ?? [])
            + count($payload['stocks'] ?? [])
            + count($payload['transfers'] ?? [])
            + count($payload['formulas'] ?? [])
            + count($payload['products'] ?? []);

        if ($totalItems === 0) {
            return ['pushed' => 0, 'message' => 'No hay registros locales pendientes por subir.'];
        }

        $response = Http::timeout(25)
            ->withHeaders(['X-Sync-Token' => $this->syncToken])
            ->post("{$this->cloudUrl}/api/cloud-sync/push", [
                'node_role' => $this->nodeRole,
                'payload'   => $payload,
            ]);

        if (!$response->successful()) {
            throw new \Exception('El VPS rechazó el envío de registros: ' . $response->body());
        }

        return [
            'pushed'  => $totalItems,
            'message' => "Se subieron {$totalItems} registros al VPS con éxito.",
        ];
    }

    /**
     * Descarga los deltas nuevos desde el VPS y los aplica localmente
     */
    public function pullDeltas(?Carbon $since = null): array
    {
        $querySince = $since ?: $this->getLastSyncTime();

        $response = Http::timeout(25)
            ->withHeaders(['X-Sync-Token' => $this->syncToken])
            ->post("{$this->cloudUrl}/api/cloud-sync/pull", [
                'node_role' => $this->nodeRole,
                'since'     => $querySince ? $querySince->toISOString() : null,
            ]);

        if (!$response->successful()) {
            throw new \Exception('El VPS falló al responder la consulta de bajada: ' . $response->body());
        }

        $cloudPayload = $response->json('payload') ?: [];
        $imported = $this->importDeltas($cloudPayload);

        return [
            'pulled'  => $imported,
            'message' => "Se descargaron y actualizaron {$imported} registros desde el VPS.",
        ];
    }

    /**
     * Ciclo completo de Sincronización Bidireccional (Push + Pull)
     */
    public function syncAll(): array
    {
        $ping = $this->pingCloud();
        if (!$ping['connected']) {
            CloudSyncLog::create([
                'node_role'      => $this->nodeRole,
                'direction'      => 'both',
                'status'         => 'failed',
                'records_pushed' => 0,
                'records_pulled' => 0,
                'error_message'  => $ping['message'],
                'synced_at'      => now(),
            ]);

            return [
                'success' => false,
                'message' => $ping['message'],
            ];
        }

        $recordsPushed = 0;
        $recordsPulled = 0;

        try {
            // 1. Subida (Push)
            $pushRes = $this->pushDeltas();
            $recordsPushed = $pushRes['pushed'];

            // 2. Bajada (Pull)
            $pullRes = $this->pullDeltas();
            $recordsPulled = $pullRes['pulled'];

            CloudSyncLog::create([
                'node_role'      => $this->nodeRole,
                'direction'      => 'both',
                'status'         => 'success',
                'records_pushed' => $recordsPushed,
                'records_pulled' => $recordsPulled,
                'synced_at'      => now(),
            ]);

            return [
                'success'        => true,
                'records_pushed' => $recordsPushed,
                'records_pulled' => $recordsPulled,
                'message'        => "Sincronización completada: {$recordsPushed} subidos, {$recordsPulled} bajados.",
            ];
        } catch (\Exception $e) {
            CloudSyncLog::create([
                'node_role'      => $this->nodeRole,
                'direction'      => 'both',
                'status'         => 'failed',
                'records_pushed' => $recordsPushed,
                'records_pulled' => $recordsPulled,
                'error_message'  => $e->getMessage(),
                'synced_at'      => now(),
            ]);

            return [
                'success' => false,
                'message' => 'Error durante la sincronización: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resuelve de forma segura el ID de usuario o genera uno por defecto para evitar fallas por FK
     */
    protected function resolveUserId(?string $email): int
    {
        if (!empty($email)) {
            $user = User::where('email', $email)->first();
            if ($user) {
                return $user->id;
            }
        }

        $fallback = User::first();
        if (!$fallback) {
            $fallback = User::create([
                'name'     => 'Operario Fábrica',
                'email'    => $email ?: 'operario@plasticosmyf.com',
                'password' => bcrypt('password123'),
            ]);
        }

        return $fallback->id;
    }

    /**
     * Resuelve de forma segura el ID de máquina o crea una máquina estándar para evitar null pointer
     */
    protected function resolveMachineId(?string $code): int
    {
        if (!empty($code)) {
            $machine = BagMachine::where('code', $code)->first();
            if (!$machine) {
                $machine = BagMachine::create([
                    'name'      => 'Máquina ' . $code,
                    'code'      => $code,
                    'type'      => 'selladora',
                    'is_active' => true,
                ]);
            }
            return $machine->id;
        }

        $fallback = BagMachine::first();
        if (!$fallback) {
            $fallback = BagMachine::create([
                'name'      => 'Máquina M01',
                'code'      => 'M01',
                'type'      => 'selladora',
                'is_active' => true,
            ]);
        }

        return $fallback->id;
    }

    /**
     * Resuelve de forma segura el ID de producto o crea un SKU genérico
     */
    protected function resolveProductId(?string $sku): int
    {
        if (!empty($sku)) {
            $product = BagProduct::where('sku', $sku)->first();
            if ($product) {
                return $product->id;
            }
        }

        $fallback = BagProduct::first();
        if (!$fallback) {
            $fallback = BagProduct::create([
                'sku'                    => $sku ?: 'PROD-GEN',
                'name'                   => 'Bolsa Estándar',
                'category'               => 'General',
                'material_type'          => 'Baja Densidad',
                'width_inch'             => 10,
                'length_inch'            => 15,
                'gauge_caliber'          => 1,
                'unit_weight_kg'         => 0.01,
                'millar_per_bulto'       => 1,
                'price'                  => 1,
                'cost'                   => 0.5,
                'is_active'              => true,
            ]);
        }

        return $fallback->id;
    }
}
