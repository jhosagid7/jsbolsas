<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BagProduct;
use App\Models\BagProduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BagsProductionApiController extends Controller
{
    /**
     * Get products for mobile bags module.
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
            ->get(['id', 'name', 'sku', 'cost', 'price', 'is_variable_quantity']);

        return response()->json($products);
    }

    /**
     * Store bags production.
     */
    public function store(Request $request)
    {
        $request->validate([
            'production_date'         => 'required|date_format:Y-m-d',
            'notes'                   => 'nullable|string',
            'details'                 => 'required|array|min:1',
            'details.*.product_id'    => 'required|exists:bag_products,id',
            'details.*.quantity'      => 'required|numeric|min:0.0001',
            'details.*.weight'        => 'required|numeric|min:0.0001',
            'details.*.operator_name' => 'nullable|string|max:255',
            'details.*.production_date' => 'nullable|date_format:Y-m-d',
            'details.*.metadata'      => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $userId = auth()->id();
            $createdIds = [];

            foreach ($request->details as $item) {
                $prod = BagProduction::create([
                    'user_id'         => $userId,
                    'product_id'      => $item['product_id'],
                    'quantity'        => $item['quantity'],
                    'weight'          => $item['weight'],
                    'recorded_at'     => $item['production_date'] ?? $request->production_date,
                    'status'          => 'pending_review',
                    'original_weight' => $item['weight'],
                    'metadata'        => array_merge($item['metadata'] ?? [], [
                        'notes'         => $request->notes,
                        'operator_name' => $item['operator_name'] ?? (auth()->user()->name ?? 'Operario'),
                    ]),
                ]);
                $createdIds[] = $prod->id;
            }

            DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => 'Levantamiento de producción registrado correctamente',
                'production_ids' => $createdIds,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la producción: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch production history.
     */
    public function history(Request $request)
    {
        $query = BagProduction::with(['product', 'user', 'shift.machine'])->orderBy('id', 'desc');

        if ($request->filled('production_date')) {
            $query->whereDate('recorded_at', $request->production_date);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($p) use ($search) {
                $p->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $history = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $history,
        ]);
    }
}

