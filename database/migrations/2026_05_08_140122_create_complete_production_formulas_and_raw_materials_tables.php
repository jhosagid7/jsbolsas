<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Materias Primas
        if (!Schema::hasTable('raw_materials')) {
            Schema::create('raw_materials', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50)->nullable()->unique();
                $table->text('description')->nullable();
                $table->decimal('base_price', 10, 4)->default(0.0000);
                $table->decimal('transport_cost', 10, 4)->default(0.1300);
                $table->decimal('surcharge', 10, 4)->default(0.1300);
                $table->decimal('final_price', 10, 4)->default(0.0000);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Historial de Precios de Materias Primas
        if (!Schema::hasTable('raw_material_price_histories')) {
            Schema::create('raw_material_price_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('raw_material_id');
                $table->decimal('base_price', 10, 4);
                $table->decimal('transport_cost', 10, 4)->default(0.1300);
                $table->decimal('surcharge', 10, 4)->default(0.1300);
                $table->decimal('final_price', 10, 4);
                $table->timestamp('valid_from')->useCurrent();
                $table->timestamp('valid_to')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('raw_material_id')->references('id')->on('raw_materials')->onDelete('cascade');
            });
        }

        // 3. Fórmulas de Producción (Actualizar columnas si existe tabla antigua o crear)
        if (!Schema::hasTable('production_formulas')) {
            Schema::create('production_formulas', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50)->nullable()->unique();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('current_version_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('production_formulas', function (Blueprint $table) {
                if (Schema::hasColumn('production_formulas', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->change();
                }
                if (Schema::hasColumn('production_formulas', 'ingredient_id')) {
                    $table->unsignedBigInteger('ingredient_id')->nullable()->change();
                }
                if (Schema::hasColumn('production_formulas', 'quantity')) {
                    $table->decimal('quantity', 10, 4)->nullable()->change();
                }
                if (!Schema::hasColumn('production_formulas', 'name')) {
                    $table->string('name')->default('Fórmula General')->after('id');
                }
                if (!Schema::hasColumn('production_formulas', 'code')) {
                    $table->string('code', 50)->nullable()->after('name');
                }
                if (!Schema::hasColumn('production_formulas', 'description')) {
                    $table->text('description')->nullable()->after('code');
                }
                if (!Schema::hasColumn('production_formulas', 'current_version_id')) {
                    $table->unsignedBigInteger('current_version_id')->nullable()->after('description');
                }
                if (!Schema::hasColumn('production_formulas', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('current_version_id');
                }
            });
        }

        // 4. Versiones de Fórmulas
        if (!Schema::hasTable('formula_versions')) {
            Schema::create('formula_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('production_formula_id');
                $table->integer('version_number')->default(1);
                $table->decimal('total_kg', 10, 4)->default(0.0000);
                $table->decimal('total_cost', 10, 4)->default(0.0000);
                $table->decimal('cost_per_kg', 10, 4)->default(0.0000);
                $table->boolean('is_active')->default(true);
                $table->timestamp('valid_from')->useCurrent();
                $table->timestamp('valid_to')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('production_formula_id')->references('id')->on('production_formulas')->onDelete('cascade');
            });
        }

        // 5. Items/Ingredientes de Versiones de Fórmulas
        if (!Schema::hasTable('formula_version_items')) {
            Schema::create('formula_version_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('formula_version_id');
                $table->unsignedBigInteger('raw_material_id');
                $table->decimal('quantity_kg', 10, 4)->default(0.0000);
                $table->decimal('price_applied', 10, 4)->default(0.0000);
                $table->decimal('subtotal_cost', 10, 4)->default(0.0000);
                $table->timestamps();

                $table->foreign('formula_version_id')->references('id')->on('formula_versions')->onDelete('cascade');
                $table->foreign('raw_material_id')->references('id')->on('raw_materials')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('formula_version_items');
        Schema::dropIfExists('formula_versions');
        Schema::dropIfExists('raw_material_price_histories');
        Schema::dropIfExists('raw_materials');
    }
};
