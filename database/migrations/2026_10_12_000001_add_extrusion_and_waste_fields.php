<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bag_products', function (Blueprint $table) {
            if (!Schema::hasColumn('bag_products', 'gusset_cm')) {
                $table->decimal('gusset_cm', 8, 2)->nullable()->after('length_inch');
            }
            if (!Schema::hasColumn('bag_products', 'material_type')) {
                $table->string('material_type', 50)->nullable()->after('category');
            }
        });

        Schema::table('bag_productions', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            if (!Schema::hasColumn('bag_productions', 'production_formula_id')) {
                $table->unsignedBigInteger('production_formula_id')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('bag_productions', 'waste_weight_kg')) {
                $table->decimal('waste_weight_kg', 10, 4)->default(0.0000)->after('weight');
            }
            if (!Schema::hasColumn('bag_productions', 'is_waste_record')) {
                $table->boolean('is_waste_record')->default(false)->after('waste_weight_kg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bag_products', function (Blueprint $table) {
            if (Schema::hasColumn('bag_products', 'gusset_cm')) {
                $table->dropColumn('gusset_cm');
            }
            if (Schema::hasColumn('bag_products', 'material_type')) {
                $table->dropColumn('material_type');
            }
        });

        Schema::table('bag_productions', function (Blueprint $table) {
            if (Schema::hasColumn('bag_productions', 'production_formula_id')) {
                $table->dropColumn('production_formula_id');
            }
            if (Schema::hasColumn('bag_productions', 'waste_weight_kg')) {
                $table->dropColumn('waste_weight_kg');
            }
            if (Schema::hasColumn('bag_productions', 'is_waste_record')) {
                $table->dropColumn('is_waste_record');
            }
        });
    }
};