<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BagShift;
use App\Models\BagProduction;
use App\Models\BagProduct;
use App\Models\BagMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BagFactoryApiController extends Controller
{
    /**
     * Get products catalog for Bag Factory.
     */
    public function products(Request $request)
    {
        $query = BagProduct::where('is_active', true);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')
            ->get(['id', 'name', 'sku', 'cost', 'price', 'is_variable_quantity', 'sale_unit', 'width_inch', 'length_inch', 'gauge_caliber', 'millar_per_bulto', 'unit_weight_kg', 'real_total_weight_kg', 'target_units_per_shift', 'target_daily_profit']);

        return response()->json($products);
    }

    /**
     * Get active machines catalog for Bag Factory.
     */
    public function machines(Request $request)
    {
        $machines = BagMachine::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type', 'is_active'])
            ->map(function ($m) {
                return [
                    'id'     => $m->id,
                    'code'   => $m->code,
                    'name'   => $m->name,
                    'type'   => strtoupper($m->type),
                    'status' => $m->is_active ? 'Operativa' : 'Mantenimiento',
                ];
            });

        return response()->json($machines);
    }

    /**
     * Open a new shift for the operator.
     */
    public function openShift(Request $request)
    {
        $request->validate([
            'shift_type' => 'nullable|in:diurno,nocturno',
            'machine_id' => 'nullable|exists:bag_machines,id',
            'user_id'    => 'nullable|exists:users,id',
            'start_time' => 'required|date',
            'sync_id'    => 'nullable|string|max:64',
            'notes'      => 'nullable|string',
        ]);

        $userId = $request->filled('user_id') ? $request->user_id : auth()->id();

        // Idempotency check with sync_id
        if ($request->filled('sync_id')) {
            $existing = BagShift::where('sync_id', $request->sync_id)->with('machine')->first();
            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Turno recuperado por sync_id',
                    'data'    => $existing,
                    'shift'   => $existing,
                ]);
            }
        }

        // Check if operator already has an active open shift
        $activeShift = BagShift::where('user_id', $userId)
            ->where('status', 'open')
            ->with('machine')
            ->first();

        if ($activeShift) {
            $machineName = $activeShift->machine ? $activeShift->machine->name : 'otra máquina';
            return response()->json([
                'success' => false,
                'message' => 'Ya posees un turno activo en la máquina ' . $machineName,
                'error'   => 'Ya posees un turno activo en la máquina ' . $machineName,
                'data'    => $activeShift,
            ], 400);
        }

        $machineId = $request->machine_id;
        $shiftType = $request->shift_type ?: 'diurno';
        $shiftCode = 'TURNO-' . date('Ymd') . ($machineId ? ('-M' . $machineId) : '') . '-' . strtoupper(Str::random(4));

        $shift = BagShift::create([
            'user_id'    => $userId,
            'machine_id' => $machineId,
            'shift_type' => $shiftType,
            'start_time' => Carbon::parse($request->start_time),
            'status'     => 'open',
            'notes'      => $request->notes,
            'sync_id'    => $request->sync_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Turno iniciado correctamente',
            'data'    => $shift->load('machine'),
            'shift'   => $shift->load('machine'),
        ]);
    }

    /**
     * Get active shift for current operator.
     */
    public function activeShift(Request $request)
    {
        $userId = auth()->id();

        $shift = BagShift::where('user_id', $userId)
            ->where('status', 'open')
            ->with(['machine', 'productions' => function ($q) {
                $q->orderBy('recorded_at', 'desc')->with('product');
            }])
            ->first();

        return response()->json([
            'success'          => true,
            'has_active_shift' => (bool)$shift,
            'data'             => $shift,
        ]);
    }

    /**
     * Synchronize offline production items in batch.
     */
    public function syncProductions(Request $request)
    {
        $request->validate([
            'shift_id'                  => 'nullable|integer',
            'shift_sync_id'             => 'nullable|string',
            'productions'               => 'required|array|min:1',
            'productions.*.sync_id'     => 'required|string|max:64',
            'productions.*.product_id'  => 'required|exists:bag_products,id',
            'productions.*.machine_id'  => 'nullable|exists:bag_machines,id',
            'productions.*.quantity'    => 'required|numeric|min:0.0001',
            'productions.*.weight'      => 'required|numeric|min:0.0001',
            'productions.*.recorded_at' => 'required|date',
            'productions.*.metadata'    => 'nullable|array',
        ]);

        $userId = auth()->id();

        $shift = null;
        if ($request->filled('shift_id')) {
            $shift = BagShift::where('id', $request->shift_id)->first();
        }
        if (!$shift && $request->filled('shift_sync_id')) {
            $shift = BagShift::where('sync_id', $request->shift_sync_id)->first();
        }
        if (!$shift) {
            $shift = BagShift::where('user_id', $userId)->where('status', 'open')->first();
        }
        if (!$shift) {
            $shift = BagShift::where('user_id', $userId)->orderBy('id', 'desc')->first();
        }
        if (!$shift) {
            $shift = BagShift::create([
                'user_id'    => $userId,
                'shift_type' => 'diurno',
                'start_time' => now(),
                'status'     => 'open',
                'sync_id'    => $request->shift_sync_id ?? ('SHIFT-' . Str::uuid()),
            ]);
        }

        $syncedIds = [];

        DB::beginTransaction();
        try {
            $user = \App\Models\User::find($userId);

            foreach ($request->productions as $item) {
                $machineId = !empty($item['machine_id']) ? $item['machine_id'] : $shift->machine_id;
                $product = BagProduct::find($item['product_id']);

                $qty = (float)$item['quantity'];
                $weight = (float)$item['weight'];

                // Auto-sanitización si el peso fue ingresado en gramos desde báscula de mesa (ej. 12030 g -> 12.03 Kg)
                if ($weight >= 500 && $product && ($weight / 1000) <= ($qty * ($product->unit_weight_kg ?: 5.0) * 10)) {
                    $weight = round($weight / 1000, 4);
                }

                $breakdown = $product ? $product->calculateBreakdown($qty) : [
                    'completed_packages'   => $qty,
                    'fractional_units'     => 0.0,
                    'is_package_completed' => true,
                ];
                $completedCount = (float)$breakdown['completed_packages'];
                $fractionalUnits = (float)$breakdown['fractional_units'];
                $isCompleted = (bool)$breakdown['is_package_completed'];

                $grading = $product ? $product->calculateWeightQualityGrade($weight, $completedCount, $fractionalUnits) : [
                    'grade'             => 'B',
                    'deviation_percent' => 0.0,
                ];
                $grade = $grading['grade'];
                $devPercent = $grading['deviation_percent'];

                $laborEarned = 0.00;
                $laborRetained = 0.00;

                if ($user && $product) {
                    $tariffs = $user->calculateLaborTariff($product);
                    $packageTariff = (float)$tariffs['package_tariff'];
                    $fractionTariff = (float)$tariffs['fraction_tariff'];

                    if ($user->pay_partial_packages) {
                        $laborEarned = round(($completedCount * $packageTariff) + ($fractionalUnits * $fractionTariff), 2);
                        $laborRetained = 0.00;
                    } else {
                        $earnedForCompleted = round($completedCount * $packageTariff, 2);
                        $retainedForFraction = round($fractionalUnits * $fractionTariff, 2);
                        $laborEarned = round($earnedForCompleted + $retainedForFraction, 2);
                        $laborRetained = $retainedForFraction;
                    }
                }

                $snapshot = [
                    'product_name'            => $product?->name,
                    'sku'                     => $product?->sku,
                    'unit_weight_kg'          => (float)($product?->unit_weight_kg ?? 0),
                    'millar_per_bulto'        => (float)($product?->millar_per_bulto ?? 1),
                    'target_units_per_shift'  => (int)($product?->target_units_per_shift ?? 5),
                    'cost_per_kg_snapshot'    => $product ? (float)$product->getEffectivePricePerKg() : 0.0,
                    'factory_price_snapshot'  => (float)($product?->price ?? 0),
                    'applied_daily_salary'    => $user ? (float)$user->daily_salary : 0.0,
                    'applied_package_tariff'  => isset($packageTariff) ? $packageTariff : 0.0,
                    'applied_fraction_tariff' => isset($fractionTariff) ? $fractionTariff : 0.0,
                ];

                $rawMeta = $item['metadata'] ?? null;
                if (is_array($rawMeta) && isset($rawMeta[0])) {
                    // Sequential list of rolls: preserve exact structure
                    $itemMeta = $rawMeta;
                } elseif (is_array($rawMeta)) {
                    $itemMeta = $rawMeta;
                    $itemMeta['snapshot'] = $snapshot;
                } else {
                    $itemMeta = $rawMeta;
                }

                $prod = BagProduction::updateOrCreate(
                    ['sync_id' => $item['sync_id']],
                    [
                        'bag_shift_id'             => $shift->id,
                        'user_id'                  => $userId,
                        'product_id'               => $item['product_id'],
                        'machine_id'               => $machineId,
                        'quantity'                 => $qty,
                        'weight'                   => $weight,
                        'weight_quality_grade'     => $grade,
                        'weight_deviation_percent' => $devPercent,
                        'completed_packages_count' => $completedCount,
                        'fractional_units'         => $fractionalUnits,
                        'is_package_completed'     => $isCompleted,
                        'labor_earned_amount'      => $laborEarned,
                        'labor_retained_amount'    => $laborRetained,
                        'recorded_at'              => Carbon::parse($item['recorded_at']),
                        'status'                   => $item['status'] ?? 'pending_review',
                        'metadata'                 => $itemMeta,
                    ]
                );

                // Collaborative fraction completion: check for pending fraction of same product
                $openFraction = BagProduction::where('product_id', $item['product_id'])
                    ->where('is_package_completed', false)
                    ->where('labor_retained_amount', '>', 0)
                    ->where('id', '!=', $prod->id)
                    ->orderBy('id', 'asc')
                    ->first();

                if ($openFraction && ($completedCount > 0 || $fractionalUnits > 0)) {
                    $openFraction->completeFractionWith($prod);
                }

                $syncedIds[] = $prod->id;
            }

            // If shift was opened under admin/superadmin, reassign shift owner to the actual working operator
            if ($shift->user_id !== $userId && ($shift->user?->role === 'superadmin' || $shift->user?->role === 'admin')) {
                $shift->user_id = $userId;
            }

            // Recalculate shift totals
            $shift->recalculateTotals();

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => 'Producción sincronizada exitosamente',
                'synced_count' => count($syncedIds),
                'shift'        => $shift->fresh(['productions.product']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar producción: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Close an open shift.
     */
    public function closeShift(Request $request)
    {
        $request->validate([
            'shift_id' => 'nullable|integer',
            'sync_id'  => 'nullable|string',
            'end_time' => 'required|date',
            'notes'    => 'nullable|string',
        ]);

        $userId = auth()->id();

        $shift = null;
        if ($request->filled('shift_id')) {
            $shift = BagShift::where('id', $request->shift_id)->where('user_id', $userId)->first();
        }
        if (!$shift && $request->filled('sync_id')) {
            $shift = BagShift::where('sync_id', $request->sync_id)->where('user_id', $userId)->first();
        }
        if (!$shift) {
            $shift = BagShift::where('user_id', $userId)->where('status', 'open')->first();
        }

        if ($shift) {
            $shift->update([
                'end_time' => Carbon::parse($request->end_time),
                'status'   => 'closed',
                'notes'    => $request->notes ?? $shift->notes,
            ]);
            $shift->recalculateTotals();
        }

        return response()->json([
            'success' => true,
            'message' => 'Turno cerrado correctamente',
            'data'    => $shift ? $shift->fresh(['productions.product']) : null,
        ]);
    }

    /**
     * Get shift history for the operator or supervisor.
     */
    public function shiftsHistory(Request $request)
    {
        $query = BagShift::with(['user', 'machine', 'productions.product'])->orderBy('start_time', 'desc');

        if (!auth()->user()->hasRole('Admin') && !auth()->user()->can('adjustments.approve_cargo')) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('date')) {
            $query->whereDate('start_time', $request->date);
        }

        if ($request->filled('machine_id')) {
            $query->where('machine_id', $request->machine_id);
        }

        if ($request->filled('shift_type')) {
            $query->where('shift_type', $request->shift_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $shifts = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $shifts,
        ]);
    }

    // ==================== SUPERVISOR / OPERATIONS MANAGER ====================

    /**
     * Supervisor live feed of productions (pending or filtered by status).
     */
    public function supervisorFeed(Request $request)
    {
        $query = BagProduction::with(['user', 'product', 'shift.machine', 'reviewer'])
            ->orderBy('recorded_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            if ($request->boolean('only_pending', true)) {
                $query->where('status', 'pending_review');
            }
        }

        if ($request->filled('shift_id')) {
            $query->where('bag_shift_id', $request->shift_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('recorded_at', $request->date);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $productions = $query->get()->map(function ($p) {
            return [
                'id'               => $p->id,
                'bag_shift_id'     => $p->bag_shift_id,
                'shift_type'       => $p->shift->shift_type ?? 'diurno',
                'user_id'          => $p->user_id,
                'operator_name'    => $p->user->name ?? 'Operario',
                'product_id'       => $p->product_id,
                'product_name'     => $p->product->name ?? 'Bolsa',
                'sku'              => $p->product->sku ?? '',
                'quantity'         => (float)$p->quantity,
                'weight'           => (float)$p->weight,
                'original_weight'  => $p->original_weight ? (float)$p->original_weight : null,
                'recorded_at'      => $p->recorded_at?->toDateTimeString(),
                'status'           => $p->status,
                'qr_code'          => $p->qr_code,
                'metadata'         => $p->metadata,
                'rejection_reason' => $p->rejection_reason,
                'reviewed_by_name' => $p->reviewer->name ?? null,
                'reviewed_at'      => $p->reviewed_at?->toDateTimeString(),
            ];
        });

        $totalPackages = $productions->sum('quantity');
        $totalWeight = $productions->sum('weight');

        return response()->json([
            'success' => true,
            'totals'  => [
                'count'          => count($productions),
                'total_packages' => (float)$totalPackages,
                'total_weight'   => (float)$totalWeight,
            ],
            'data'    => $productions,
        ]);
    }

    /**
     * Supervisor scale adjustment (edit weight or quantity).
     */
    public function adjustProduction(Request $request, $id)
    {
        $request->validate([
            'weight'   => 'required|numeric|min:0.0001',
            'quantity' => 'nullable|numeric|min:0.0001',
            'notes'    => 'nullable|string',
            'metadata' => 'nullable|array',
            'rolls'    => 'nullable|array',
        ]);

        $prod = BagProduction::findOrFail($id);

        if ((float)$prod->weight !== (float)$request->weight) {
            $prod->original_weight = $prod->original_weight ?? $prod->weight;
            $prod->weight = $request->weight;
        }

        if ($request->filled('quantity')) {
            $prod->quantity = $request->quantity;
        }

        if ($request->has('metadata')) {
            $prod->metadata = $request->metadata;
        } elseif ($request->has('rolls')) {
            $prod->metadata = $request->rolls;
        }

        $prod->reviewed_by = auth()->id();
        $prod->save();

        $prod->shift?->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Pesaje y cantidad actualizados correctamente',
            'data'    => $prod->fresh(['product', 'user', 'reviewer']),
        ]);
    }

    /**
     * Approve single production for Pre-Stock (with auto-split for multi-package homogenous items).
     */
    public function approveProduction(Request $request, $id)
    {
        $prod = BagProduction::with(['product', 'user', 'shift.user'])->findOrFail($id);

        if ($prod->quantity > 1 && !$prod->product?->is_composite_rolls) {
            $totalQty = (int)$prod->quantity;
            $totalWeight = (float)$prod->weight;
            $rolls = [];

            if (!empty($prod->metadata)) {
                if (is_array($prod->metadata) && isset($prod->metadata['rolls']) && is_array($prod->metadata['rolls'])) {
                    $rolls = $prod->metadata['rolls'];
                } elseif (is_array($prod->metadata) && isset($prod->metadata[0]['weight'])) {
                    $rolls = $prod->metadata;
                }
            }

            $createdIds = [];

            DB::beginTransaction();
            try {
                for ($i = 0; $i < $totalQty; $i++) {
                    $newProd = $prod->replicate();
                    $newProd->quantity = 1.0;
                    $rollWeight = isset($rolls[$i]['weight']) && (float)$rolls[$i]['weight'] > 0
                        ? (float)$rolls[$i]['weight']
                        : round($totalWeight / $totalQty, 2);
                    $newProd->weight = $rollWeight;
                    $newProd->qr_code = 'PKG-' . strtoupper(Str::random(10));
                    $newProd->status = 'approved';
                    $newProd->reviewed_at = now();
                    $newProd->reviewed_by = auth()->id();
                    $newProd->sync_id = 'PROD-SPLIT-' . Str::uuid();
                    $newProd->metadata = isset($rolls[$i]) ? ['roll' => $rolls[$i]] : null;
                    $newProd->save();
                    $createdIds[] = $newProd->id;
                }
                $prod->delete();
                DB::commit();

                $firstCreated = BagProduction::with(['product', 'user', 'reviewer'])->find($createdIds[0] ?? null);

                return response()->json([
                    'success'       => true,
                    'message'       => "Lote dividido y aprobado en {$totalQty} unidades individuales",
                    'is_split'      => true,
                    'split_count'   => $totalQty,
                    'data'          => $firstCreated,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al dividir lote de bultos/bobinas: ' . $e->getMessage(),
                ], 500);
            }
        }

        if (empty($prod->qr_code)) {
            $prod->qr_code = 'PKG-' . strtoupper(Str::random(10));
        }

        $prod->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unidad aprobada para Pre-Levantamiento',
            'data'    => $prod->fresh(['product', 'user', 'reviewer']),
        ]);
    }

    /**
     * Bulk approve multiple productions (with auto-split support).
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'production_ids' => 'required|array|min:1',
            'production_ids.*' => 'exists:bag_productions,id',
        ]);

        $count = 0;
        DB::transaction(function () use ($request, &$count) {
            foreach ($request->production_ids as $id) {
                $prod = BagProduction::with(['product'])->find($id);
                if ($prod && $prod->status !== 'approved') {
                    if ($prod->quantity > 1 && !$prod->product?->is_composite_rolls) {
                        $totalQty = (int)$prod->quantity;
                        $totalWeight = (float)$prod->weight;
                        $rolls = [];

                        if (!empty($prod->metadata)) {
                            if (is_array($prod->metadata) && isset($prod->metadata['rolls']) && is_array($prod->metadata['rolls'])) {
                                $rolls = $prod->metadata['rolls'];
                            } elseif (is_array($prod->metadata) && isset($prod->metadata[0]['weight'])) {
                                $rolls = $prod->metadata;
                            }
                        }

                        for ($i = 0; $i < $totalQty; $i++) {
                            $newProd = $prod->replicate();
                            $newProd->quantity = 1.0;
                            $rollWeight = isset($rolls[$i]['weight']) && (float)$rolls[$i]['weight'] > 0
                                ? (float)$rolls[$i]['weight']
                                : round($totalWeight / $totalQty, 2);
                            $newProd->weight = $rollWeight;
                            $newProd->qr_code = 'PKG-' . strtoupper(Str::random(10));
                            $newProd->status = 'approved';
                            $newProd->reviewed_at = now();
                            $newProd->reviewed_by = auth()->id();
                            $newProd->sync_id = 'PROD-SPLIT-' . Str::uuid();
                            $newProd->metadata = isset($rolls[$i]) ? ['roll' => $rolls[$i]] : null;
                            $newProd->save();
                            $count++;
                        }
                        $prod->delete();
                    } else {
                        if (empty($prod->qr_code)) {
                            $prod->qr_code = 'PKG-' . strtoupper(Str::random(10));
                        }
                        $prod->status = 'approved';
                        $prod->reviewed_by = auth()->id();
                        $prod->reviewed_at = now();
                        $prod->save();
                        $count++;
                    }
                }
            }
        });

        return response()->json([
            'success'        => true,
            'message'        => "Se aprobaron {$count} unidad(es) exitosamente",
            'approved_count' => $count,
        ]);
    }

    /**
     * Reject a production with reason.
     */
    public function rejectProduction(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:3',
        ]);

        $prod = BagProduction::findOrFail($id);

        $prod->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        $prod->shift?->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Bulto marcado como rechazado',
            'data'    => $prod->fresh(['product', 'user', 'reviewer']),
        ]);
    }

    /**
     * Pre-stock inventory (approved items ready for general warehouse lifting).
     */
    public function preStock(Request $request)
    {
        $query = BagProduction::where('status', 'approved')
            ->with(['product', 'user', 'shift'])
            ->orderBy('reviewed_at', 'desc');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('reviewed_at', $request->date);
        }

        $items = $query->get();

        $totalPackages = $items->sum('quantity');
        $totalWeight = $items->sum('weight');

        return response()->json([
            'success' => true,
            'totals'  => [
                'total_packages' => (float)$totalPackages,
                'total_weight'   => (float)$totalWeight,
                'items_count'    => count($items),
            ],
            'data'    => $items,
        ]);
    }

    /**
     * Ticket & QR Data for thermal printer.
     */
    public function ticketData(Request $request, $id)
    {
        $prod = BagProduction::with(['product', 'user', 'shift.machine', 'reviewer'])->findOrFail($id);

        if (empty($prod->qr_code)) {
            $prod->qr_code = 'PKG-' . strtoupper(Str::random(10));
            $prod->save();
        }

        $machine = $prod->shift?->machine;

        $data = [
            'id'            => $prod->id,
            'qr_code'       => $prod->qr_code,
            'product_name'  => $prod->product->name ?? 'Bolsa',
            'sku'           => $prod->product->sku ?? '',
            'operator_name' => $prod->user->name ?? 'Operario',
            'machine_code'  => $machine?->code ?? '',
            'machine_name'  => $machine?->name ?? '',
            'machine_label' => $machine ? ($machine->code . ' (' . $machine->name . ')') : '',
            'shift_type'    => strtoupper($prod->shift->shift_type ?? 'DIURNO'),
            'quantity'      => (float)$prod->quantity,
            'weight'        => (float)$prod->weight,
            'recorded_at'   => $prod->recorded_at?->format('d/m/Y h:i A'),
            'status'        => $prod->status,
            'reviewed_by'   => $prod->reviewer->name ?? 'Supervisor de Planta',
        ];

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // ==================== JSPOS GENERAL WAREHOUSE LIFTING (RECEPCIÓN) ====================

    /**
     * List all approved factory bags ready for JSPOS warehouse lifting.
     */
    public function liftingPending(Request $request)
    {
        $query = BagProduction::readyForLifting()
            ->with(['product', 'user', 'shift', 'reviewer'])
            ->orderBy('reviewed_at', 'desc');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('qr_code', 'like', "%{$s}%")
                  ->orWhereHas('product', function ($sub) use ($s) {
                      $sub->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
                  })
                  ->orWhereHas('user', function ($sub) use ($s) {
                      $sub->where('name', 'like', "%{$s}%");
                  });
            });
        }

        $items = $query->get()->map(function ($bp) {
            return [
                'id'            => $bp->id,
                'qr_code'       => $bp->qr_code,
                'product_id'    => $bp->product_id,
                'product_name'  => $bp->product->name ?? 'Bolsa',
                'sku'           => $bp->product->sku ?? '',
                'quantity'      => (float)$bp->quantity,
                'weight'        => (float)$bp->weight,
                'operator_name' => $bp->user->name ?? 'Operario',
                'shift_type'    => $bp->shift->shift_type ?? 'diurno',
                'reviewed_by'   => $bp->reviewer->name ?? 'Supervisor',
                'reviewed_at'   => $bp->reviewed_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'totals'  => [
                'count'          => count($items),
                'total_packages' => (float)$items->sum('quantity'),
                'total_weight'   => (float)$items->sum('weight'),
            ],
            'data'    => $items,
        ]);
    }

    /**
     * Scan QR code for fast lifting.
     */
    public function scanQr(Request $request, $code)
    {
        $prod = BagProduction::with(['product', 'user', 'shift', 'reviewer'])
            ->where('qr_code', $code)
            ->first();

        if (!$prod) {
            return response()->json([
                'success' => false,
                'message' => 'Código de bulto no encontrado',
            ], 404);
        }

        $isReady = ($prod->status === 'approved' && is_null($prod->lifted_at));

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $prod->id,
                'qr_code'       => $prod->qr_code,
                'product_id'    => $prod->product_id,
                'product_name'  => $prod->product->name ?? 'Bolsa',
                'sku'           => $prod->product->sku ?? '',
                'quantity'      => (float)$prod->quantity,
                'weight'        => (float)$prod->weight,
                'status'        => $prod->status,
                'operator_name' => $prod->user->name ?? 'Operario',
                'shift_type'    => $prod->shift->shift_type ?? 'diurno',
                'is_ready'      => $isReady,
                'lifted_at'     => $prod->lifted_at?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Confirm lifting of bultos into warehouse inventory.
     */
    public function receiveLifting(Request $request)
    {
        $request->validate([
            'production_ids'   => 'nullable|array',
            'production_ids.*' => 'exists:bag_productions,id',
            'items'            => 'nullable|array',
            'items.*.id'       => 'required_with:items|exists:bag_productions,id',
            'items.*.weight'   => 'nullable|numeric|min:0.01',
            'items.*.rolls'    => 'nullable|array',
            'notes'            => 'nullable|string',
        ]);

        $userId = auth()->id();
        $ids = $request->input('production_ids', []);
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                if (isset($item['id'])) {
                    $ids[] = $item['id'];
                }
            }
        }
        $ids = array_unique($ids);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe enviar al menos un ID de producción a levantar',
            ], 422);
        }

        $bultos = BagProduction::whereIn('id', $ids)
            ->where('status', 'approved')
            ->whereNull('lifted_at')
            ->with(['product', 'user'])
            ->get();

        if ($bultos->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron bultos válidos en estado aprobado pendientes de levantar',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $itemsMap = [];
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    if (isset($item['id'])) {
                        $itemsMap[$item['id']] = $item;
                    }
                }
            }

            foreach ($bultos as $bp) {
                if (isset($itemsMap[$bp->id])) {
                    $itemData = $itemsMap[$bp->id];
                    if (isset($itemData['rolls']) && is_array($itemData['rolls'])) {
                        $cleanRolls = [];
                        $sumW = 0;
                        foreach ($itemData['rolls'] as $r) {
                            $rw = (float)($r['weight'] ?? 0);
                            if ($rw > 0) {
                                $cleanRolls[] = [
                                    'weight' => $rw,
                                    'color'  => trim($r['color'] ?? ''),
                                    'batch'  => trim($r['batch'] ?? ''),
                                ];
                                $sumW += $rw;
                            }
                        }
                        if (!empty($cleanRolls)) {
                            $bp->metadata = $cleanRolls;
                            $bp->quantity = count($cleanRolls);
                            $bp->weight = $sumW;
                        }
                    } elseif (isset($itemData['weight']) && (float)$itemData['weight'] > 0) {
                        $bp->weight = (float)$itemData['weight'];
                    }
                }

                $bp->status = 'lifted';
                $bp->lifted_by = $userId;
                $bp->lifted_at = now();
                if ($request->filled('notes')) {
                    $meta = $bp->metadata ?? [];
                    $meta['lifting_notes'] = $request->notes;
                    $bp->metadata = $meta;
                }
                $bp->save();
            }

            DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => "Se levantaron exitosamente {$bultos->count()} bulto(s)",
                'received_count' => $bultos->count(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el levantamiento: ' . $e->getMessage(),
            ], 500);
        }
    }
}