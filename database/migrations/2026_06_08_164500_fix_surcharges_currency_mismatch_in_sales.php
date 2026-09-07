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
        // 1. Correct any sales where base_amount is stored in local currency instead of USD
        DB::table('sales')
            ->whereRaw('base_amount > (total_usd * 1.5)')
            ->where('primary_exchange_rate', '>', 1)
            ->chunkById(100, function ($sales) {
                foreach ($sales as $sale) {
                    $rate = floatval($sale->primary_exchange_rate);
                    if ($rate > 1) {
                        DB::table('sales')->where('id', $sale->id)->update([
                            'base_amount' => round(floatval($sale->base_amount) / $rate, 4),
                            'commission_amount' => round(floatval($sale->commission_amount) / $rate, 4),
                            'freight_amount' => round(floatval($sale->freight_amount) / $rate, 4),
                            'exchange_diff_amount' => round(floatval($sale->exchange_diff_amount) / $rate, 4),
                        ]);
                    }
                }
            });

        // 2. Correct any orders where base_amount is stored in local currency instead of USD
        try {
            DB::table('orders')
                ->join('currencies', 'orders.invoice_currency_id', '=', 'currencies.id')
                ->whereRaw('orders.base_amount > (orders.total * 1.5)')
                ->where('currencies.exchange_rate', '>', 1)
                ->select('orders.*', 'currencies.exchange_rate')
                ->chunkById(100, function ($orders) {
                    foreach ($orders as $order) {
                        $rate = floatval($order->exchange_rate);
                        if ($rate > 1) {
                            DB::table('orders')->where('id', $order->id)->update([
                                'base_amount' => round(floatval($order->base_amount) / $rate, 4),
                                'commission_amount' => round(floatval($order->commission_amount) / $rate, 4),
                                'freight_amount' => round(floatval($order->freight_amount) / $rate, 4),
                                'exchange_diff_amount' => round(floatval($order->exchange_diff_amount) / $rate, 4),
                            ]);
                        }
                    }
                }, 'orders.id');
        } catch (\Throwable $e) {
            // Log or ignore if table/columns don't exist
            \Illuminate\Support\Facades\Log::warning("Order surcharge correction error: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollbacks needed as this is a data correction migration
    }
};
