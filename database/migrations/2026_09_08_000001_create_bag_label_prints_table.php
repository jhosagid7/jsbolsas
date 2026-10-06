<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bag_label_prints')) {
            Schema::create('bag_label_prints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_id')->nullable()->constrained('bag_productions')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('qr_code', 100)->index();
                $table->string('label_type', 30)->default('millar'); // bulto, millar, bobina, catalog
                $table->integer('print_count')->default(1);
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bag_label_prints');
    }
};
