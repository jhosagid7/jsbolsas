<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $operators = [
            ['name' => 'Gabriel Marquez', 'email' => 'gabriel@plasticosmyf.com'],
            ['name' => 'Ernesto',         'email' => 'ernesto@plasticosmyf.com'],
            ['name' => 'Sahir',           'email' => 'sahir@plasticosmyf.com'],
            ['name' => 'Victor',          'email' => 'victor@plasticosmyf.com'],
            ['name' => 'Nestor',          'email' => 'nestor@plasticosmyf.com'],
        ];

        $roleOperario = null;
        if (class_exists(Role::class) && Schema::hasTable('roles')) {
            $roleOperario = Role::where('name', 'operario')->orWhere('name', 'Operario')->first();
            if (!$roleOperario) {
                try {
                    $roleOperario = Role::create(['name' => 'operario', 'guard_name' => 'web']);
                } catch (\Throwable $e) {
                    // Ignorar si existe
                }
            }
        }

        foreach ($operators as $op) {
            $user = User::updateOrCreate(
                ['email' => $op['email']],
                [
                    'name'               => $op['name'],
                    'password'           => Hash::make('12345678'),
                    'profile'            => 'operario',
                    'status'             => 'Active',
                    'weekly_salary'      => 90.00,
                    'work_days_per_week' => 6,
                ]
            );

            if ($roleOperario && method_exists($user, 'assignRole')) {
                try {
                    $user->assignRole($roleOperario);
                } catch (\Throwable $e) {
                    // Ignorar si ya está asignado
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo
    }
};
