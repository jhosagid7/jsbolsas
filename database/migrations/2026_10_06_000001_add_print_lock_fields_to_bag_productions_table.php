<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bag_productions', function (Blueprint $table) {
            if (!Schema::hasColumn('bag_productions', 'is_printed')) {
                $table->boolean('is_printed')->default(false)->after('status')->index();
            }
            if (!Schema::hasColumn('bag_productions', 'printed_at')) {
                $table->dateTime('printed_at')->nullable()->after('is_printed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bag_productions', function (Blueprint $table) {
            if (Schema::hasColumn('bag_productions', 'printed_at')) {
                $table->dropColumn('printed_at');
            }
            if (Schema::hasColumn('bag_productions', 'is_printed')) {
                $table->dropColumn('is_printed');
            }
        });
    }
};
