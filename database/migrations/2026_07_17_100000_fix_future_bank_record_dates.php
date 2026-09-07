<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find any bank_records where the payment_date is incorrectly set to a future date
        // compared to when it was actually created (e.g., more than 7 days ahead).
        $records = DB::table('bank_records')
            ->whereRaw('payment_date > DATE_ADD(created_at, INTERVAL 7 DAY)')
            ->get();

        foreach ($records as $r) {
            $realDate = Carbon::parse($r->created_at)->format('Y-m-d');
            DB::table('bank_records')
                ->where('id', $r->id)
                ->update(['payment_date' => $realDate]);
        }

        // Trigger balance recalculation for affected banks
        $bankIds = $records->pluck('bank_id')->unique();
        foreach ($bankIds as $bankId) {
            try {
                \App\Services\BankTreasuryService::recalculateBalance($bankId);
            } catch (\Throwable $e) {
                // Ignore if service is not ready yet
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data correction migrations are non-reversible
    }
};
