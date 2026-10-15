<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Logs de Auditoría de Sincronización Nube
        if (!Schema::hasTable('cloud_sync_logs')) {
            Schema::create('cloud_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->string('node_role', 20)->default('local'); // 'local' o 'cloud'
                $table->string('direction', 20)->default('both');  // 'push', 'pull', 'both'
                $table->string('status', 20)->default('success');   // 'success', 'failed', 'partial'
                $table->integer('records_pushed')->default(0);
                $table->integer('records_pulled')->default(0);
                $table->json('details')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('synced_at')->useCurrent();
                $table->timestamps();
            });
        }

        // 2. Asegurar columna sync_id en raw_material_transfers para idempotencia
        if (Schema::hasTable('raw_material_transfers') && !Schema::hasColumn('raw_material_transfers', 'sync_id')) {
            Schema::table('raw_material_transfers', function (Blueprint $table) {
                $table->string('sync_id', 64)->nullable()->unique()->after('id');
            });
        }

        // 3. Asegurar columna sync_id en raw_material_audits para idempotencia
        if (Schema::hasTable('raw_material_audits') && !Schema::hasColumn('raw_material_audits', 'sync_id')) {
            Schema::table('raw_material_audits', function (Blueprint $table) {
                $table->string('sync_id', 64)->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('raw_material_audits') && Schema::hasColumn('raw_material_audits', 'sync_id')) {
            Schema::table('raw_material_audits', function (Blueprint $table) {
                $table->dropColumn('sync_id');
            });
        }

        if (Schema::hasTable('raw_material_transfers') && Schema::hasColumn('raw_material_transfers', 'sync_id')) {
            Schema::table('raw_material_transfers', function (Blueprint $table) {
                $table->dropColumn('sync_id');
            });
        }

        Schema::dropIfExists('cloud_sync_logs');
    }
};
