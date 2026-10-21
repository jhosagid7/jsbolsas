<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cloud_sync_deletions')) {
            Schema::create('cloud_sync_deletions', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 40); // 'production', 'shift', 'transfer'
                $table->string('sync_id', 64)->index();
                $table->timestamps();

                $table->unique(['entity_type', 'sync_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cloud_sync_deletions');
    }
};
