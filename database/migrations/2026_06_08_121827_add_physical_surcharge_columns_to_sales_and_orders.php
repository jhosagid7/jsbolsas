<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add columns to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'route_goal')) {
                $table->decimal('route_goal', 12, 4)->default(0.0000)->nullable()->after('monthly_goal');
            }
        });

        // 2. Add columns to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'driver_id')) {
                $table->unsignedBigInteger('driver_id')->nullable()->after('user_id');
                $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('orders', 'base_amount')) {
                $table->decimal('base_amount', 12, 4)->default(0.0000)->nullable()->after('total');
            }
            if (!Schema::hasColumn('orders', 'commission_amount')) {
                $table->decimal('commission_amount', 12, 4)->default(0.0000)->nullable()->after('base_amount');
            }
            if (!Schema::hasColumn('orders', 'freight_amount')) {
                $table->decimal('freight_amount', 12, 4)->default(0.0000)->nullable()->after('commission_amount');
            }
            if (!Schema::hasColumn('orders', 'exchange_diff_amount')) {
                $table->decimal('exchange_diff_amount', 12, 4)->default(0.0000)->nullable()->after('freight_amount');
            }
        });

        // 3. Add columns to sales table
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'base_amount')) {
                $table->decimal('base_amount', 12, 4)->default(0.0000)->nullable()->after('total_usd');
            }
            if (!Schema::hasColumn('sales', 'commission_amount')) {
                $table->decimal('commission_amount', 12, 4)->default(0.0000)->nullable()->after('base_amount');
            }
            if (!Schema::hasColumn('sales', 'freight_amount')) {
                $table->decimal('freight_amount', 12, 4)->default(0.0000)->nullable()->after('commission_amount');
            }
            if (!Schema::hasColumn('sales', 'exchange_diff_amount')) {
                $table->decimal('exchange_diff_amount', 12, 4)->default(0.0000)->nullable()->after('freight_amount');
            }
        });

        // 4. Backfill historical Sales data in chunks
        DB::table('sales')->orderBy('id')->chunk(100, function ($sales) {
            foreach ($sales as $sale) {
                // Calculate base_amount from sale_details in USD
                $baseAmount = DB::table('sale_details')
                    ->where('sale_id', $sale->id)
                    ->sum(DB::raw('price_usd * quantity'));
                
                if ($baseAmount == 0 && $sale->total_usd > 0) {
                    // Fallback to reverse calculation if details have no regular_price or are missing
                    $increments = ($sale->applied_commission_percent + $sale->applied_freight_percent + $sale->applied_exchange_diff_percent) / 100;
                    $baseAmount = $sale->total_usd / (1 + $increments);
                }

                $commAmt = $baseAmount * ($sale->applied_commission_percent / 100);
                $freightAmt = $baseAmount * ($sale->applied_freight_percent / 100);
                $diffAmt = $baseAmount * ($sale->applied_exchange_diff_percent / 100);

                DB::table('sales')->where('id', $sale->id)->update([
                    'base_amount' => round($baseAmount, 4),
                    'commission_amount' => round($commAmt, 4),
                    'freight_amount' => round($freightAmt, 4),
                    'exchange_diff_amount' => round($diffAmt, 4)
                ]);
            }
        });

        // 5. Backfill historical Orders data in chunks
        // Eager-loading simulation on raw DB queries is manual. We will query Order model directly but inside try-catch to avoid potential issues.
        try {
            DB::table('orders')->orderBy('id')->chunk(100, function ($orders) {
                foreach ($orders as $order) {
                    // Calculate base_amount from order_details
                    $baseAmount = DB::table('order_details')
                        ->where('order_id', $order->id)
                        ->sum(DB::raw('regular_price * quantity'));

                    if ($baseAmount == 0 && $order->total > 0) {
                        // Fallback to reverse calculation if details have no regular_price or are missing
                        $increments = ($order->resolved_commission_percent + $order->resolved_freight_percent + $order->resolved_exchange_diff_percent) / 100;
                        $baseAmount = $order->total / (1 + $increments);
                    }

                    $commAmt = $baseAmount * ($order->resolved_commission_percent / 100);
                    $freightAmt = $baseAmount * ($order->resolved_freight_percent / 100);
                    $diffAmt = $baseAmount * ($order->resolved_exchange_diff_percent / 100);

                    DB::table('orders')->where('id', $order->id)->update([
                        'base_amount' => round($baseAmount, 4),
                        'commission_amount' => round($commAmt, 4),
                        'freight_amount' => round($freightAmt, 4),
                        'exchange_diff_amount' => round($diffAmt, 4)
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Log it but don't break migration if models aren't bootstrapped
            \Illuminate\Support\Facades\Log::warning("Order backfill error during migration: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Remove columns from sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'commission_amount', 'freight_amount', 'exchange_diff_amount']);
        });

        // 2. Remove columns from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['driver_id', 'base_amount', 'commission_amount', 'freight_amount', 'exchange_diff_amount']);
        });

        // 3. Remove columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('route_goal');
        });
    }
};
