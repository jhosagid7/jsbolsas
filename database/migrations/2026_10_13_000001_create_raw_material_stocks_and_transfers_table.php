<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Existencias de Materias Primas por Depósito (Principal vs Planta)
        if (!Schema::hasTable('raw_material_stocks')) {
            Schema::create('raw_material_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raw_material_id')->constrained('raw_materials')->onDelete('cascade');
                $table->enum('warehouse_type', ['principal', 'planta'])->default('principal');
                $table->decimal('sacos_count', 10, 2)->default(0.00); // Número de sacos (ej. sacos de 25kg)
                $table->decimal('total_kg', 12, 4)->default(0.0000);  // Kilos totales en stock
                $table->timestamps();

                $table->unique(['raw_material_id', 'warehouse_type']);
            });
        }

        // 2. Transferencias de Materia Prima entre Depósitos (Principal -> Planta)
        if (!Schema::hasTable('raw_material_transfers')) {
            Schema::create('raw_material_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raw_material_id')->constrained('raw_materials')->onDelete('cascade');
                $table->enum('from_warehouse', ['principal', 'planta'])->default('principal');
                $table->enum('to_warehouse', ['principal', 'planta'])->default('planta');
                $table->decimal('sacos_transferred', 10, 2)->default(0.00);
                $table->decimal('weight_kg', 12, 4)->default(0.0000);
                $table->foreignId('transferred_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('transferred_at')->useCurrent();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Arqueo y Balance de Masa Semanal (Corte de los Sábados)
        if (!Schema::hasTable('raw_material_audits')) {
            Schema::create('raw_material_audits', function (Blueprint $table) {
                $table->id();
                $table->date('audit_date');
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('initial_plant_kg', 12, 4)->default(0.0000);
                $table->decimal('transferred_to_plant_kg', 12, 4)->default(0.0000);
                $table->decimal('final_counted_plant_kg', 12, 4)->default(0.0000);
                $table->decimal('consumed_kg', 12, 4)->default(0.0000);     // initial + transferred - final
                $table->decimal('good_production_kg', 12, 4)->default(0.0000); // Bobinas conformes
                $table->decimal('waste_purga_kg', 12, 4)->default(0.0000);     // Merma de purga / calibración
                $table->decimal('variance_kg', 12, 4)->default(0.0000);        // consumed - (good + waste)
                $table->decimal('variance_percentage', 8, 2)->default(0.00);
                $table->enum('status', ['draft', 'approved'])->default('approved');
                $table->foreignId('audited_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_audits');
        Schema::dropIfExists('raw_material_transfers');
        Schema::dropIfExists('raw_material_stocks');
    }
};
