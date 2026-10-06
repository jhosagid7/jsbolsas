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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'weekly_salary')) {
                    $table->decimal('weekly_salary', 10, 2)->default(90.00)->after('profile');
                }
                if (!Schema::hasColumn('users', 'work_days_per_week')) {
                    $table->tinyInteger('work_days_per_week')->default(6)->after('weekly_salary');
                }
                if (!Schema::hasColumn('users', 'pay_partial_packages')) {
                    $table->boolean('pay_partial_packages')->default(false)->after('work_days_per_week');
                }
            });
        }

        if (Schema::hasTable('bag_productions')) {
            Schema::table('bag_productions', function (Blueprint $table) {
                if (!Schema::hasColumn('bag_productions', 'weight_quality_grade')) {
                    $table->string('weight_quality_grade', 10)->default('B')->after('weight');
                }
                if (!Schema::hasColumn('bag_productions', 'weight_deviation_percent')) {
                    $table->decimal('weight_deviation_percent', 8, 2)->nullable()->after('weight_quality_grade');
                }
                if (!Schema::hasColumn('bag_productions', 'completed_packages_count')) {
                    $table->decimal('completed_packages_count', 10, 2)->default(0)->after('weight_deviation_percent');
                }
                if (!Schema::hasColumn('bag_productions', 'fractional_units')) {
                    $table->decimal('fractional_units', 10, 2)->default(0)->after('completed_packages_count');
                }
                if (!Schema::hasColumn('bag_productions', 'is_package_completed')) {
                    $table->boolean('is_package_completed')->default(true)->after('fractional_units');
                }
                if (!Schema::hasColumn('bag_productions', 'labor_earned_amount')) {
                    $table->decimal('labor_earned_amount', 10, 2)->default(0)->after('is_package_completed');
                }
                if (!Schema::hasColumn('bag_productions', 'labor_retained_amount')) {
                    $table->decimal('labor_retained_amount', 10, 2)->default(0)->after('labor_earned_amount');
                }
                if (!Schema::hasColumn('bag_productions', 'completed_by_production_id')) {
                    $table->foreignId('completed_by_production_id')->nullable()->after('labor_retained_amount')->constrained('bag_productions')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bag_productions')) {
            Schema::table('bag_productions', function (Blueprint $table) {
                if (Schema::hasColumn('bag_productions', 'completed_by_production_id')) {
                    $table->dropForeign(['completed_by_production_id']);
                    $table->dropColumn('completed_by_production_id');
                }
                $columns = [
                    'weight_quality_grade',
                    'weight_deviation_percent',
                    'completed_packages_count',
                    'fractional_units',
                    'is_package_completed',
                    'labor_earned_amount',
                    'labor_retained_amount',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('bag_productions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $userCols = ['weekly_salary', 'work_days_per_week', 'pay_partial_packages'];
                foreach ($userCols as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
