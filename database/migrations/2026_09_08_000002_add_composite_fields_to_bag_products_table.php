<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bag_products', function (Blueprint $table) {
            if (!Schema::hasColumn('bag_products', 'is_composite_rolls')) {
                $table->boolean('is_composite_rolls')->default(false)->after('is_variable_quantity');
            }
            if (!Schema::hasColumn('bag_products', 'suggested_rolls_per_package')) {
                $table->integer('suggested_rolls_per_package')->default(9)->after('is_composite_rolls');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bag_products', function (Blueprint $table) {
            if (Schema::hasColumn('bag_products', 'is_composite_rolls')) {
                $table->dropColumn('is_composite_rolls');
            }
            if (Schema::hasColumn('bag_products', 'suggested_rolls_per_package')) {
                $table->dropColumn('suggested_rolls_per_package');
            }
        });
    }
};
