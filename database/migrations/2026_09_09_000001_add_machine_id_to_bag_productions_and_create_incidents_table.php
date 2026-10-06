<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bag_productions', function (Blueprint $table) {
            if (!Schema::hasColumn('bag_productions', 'machine_id')) {
                $table->foreignId('machine_id')->nullable()->after('product_id')->constrained('bag_machines')->nullOnDelete();
            }
        });

        if (!Schema::hasTable('bag_machine_incidents')) {
            Schema::create('bag_machine_incidents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('machine_id')->constrained('bag_machines')->cascadeOnDelete();
                $table->foreignId('production_id')->nullable()->constrained('bag_productions')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('bag_products')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('incident_type', 50)->default('mecanica'); // mecanica, formula, operacion, otra
                $table->string('severity', 20)->default('media'); // baja, media, alta, critica
                $table->string('title', 255);
                $table->text('description');
                $table->string('batch_code', 100)->nullable();
                $table->string('root_cause_analysis', 50)->default('desconocido'); // maquina, formula, operador, desconocido
                $table->string('status', 30)->default('abierta'); // abierta, en_revision, resuelta
                $table->text('resolution_notes')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bag_machine_incidents');

        Schema::table('bag_productions', function (Blueprint $table) {
            if (Schema::hasColumn('bag_productions', 'machine_id')) {
                $table->dropForeign(['machine_id']);
                $table->dropColumn('machine_id');
            }
        });
    }
};
