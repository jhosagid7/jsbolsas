<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('device_authorizations')) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('device_authorizations')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Throwable ) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};