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
            foreach ($request->productions as $item) {
                $prod = BagProduction::updateOrCreate(
                    ['sync_id' => $item['sync_id']],
                    [
                        'bag_shift_id' => $shift->id,
                        'user_id'      => $userId,
                        'product_id'   => $item['product_id'],
                        'quantity'     => $item['quantity'],
                        'weight'       => $item['weight'],
                        'recorded_at'  => Carbon::parse($item['recorded_at']),
                        'status'       => $item['status'] ?? 'pending_review',
                        'metadata'     => $item['metadata'] ?? null,
                    ]
                );
                $syncedIds[] = $prod->id;
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
     * Approve single production for Pre-Stock.
     */
    public function approveProduction(Request $request, $id)
    {
        $prod = BagProduction::findOrFail($id);

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
            'message' => 'Bulto aprobado para Pre-Levantamiento',
            'data'    => $prod->fresh(['product', 'user', 'reviewer']),
        ]);
    }

    /**
     * Bulk approve multiple productions.
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
                $prod = BagProduction::find($id);
                if ($prod && $prod->status !== 'approved') {
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
        });

        return response()->json([
            'success'        => true,
            'message'        => "Se aprobaron {$count} bulto(s) exitosamente",
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
             'production_ids'   => 'required|array|min:1',
             'production_ids.*' => 'exists:bag_productions,id',
             'notes'            => 'nullable|string',
         ]);

         $userId = auth()->id();

         $bultos = BagProduction::whereIn('id', $request->production_ids)
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
             foreach ($bultos as $bp) {
                 $bp->update([
                     'status'    => 'lifted',
                     'lifted_by' => $userId,
                     'lifted_at' => now(),
                 ]);
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