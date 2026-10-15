<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\BagShift;
use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\RawMaterial;
use App\Models\RawMaterialStock;

class ResetFactoryProductionTestsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_factory_production_tests_clears_records_and_resets_raw_material_stocks(): void
    {
        $user = User::factory()->create(['name' => 'Operario Test']);
        $machine = BagMachine::create([
            'name'      => 'Extrusora #1',
            'code'      => 'EXT-01',
            'type'      => 'extrusora',
            'is_active' => true,
        ]);

        $product = BagProduct::create([
            'sku'       => 'TEST-BOB-01',
            'name'      => 'Bobina Test',
            'sale_unit' => 'BOBINA',
            'is_active' => true,
        ]);

        $shift = BagShift::create([
            'user_id'    => $user->id,
            'machine_id' => $machine->id,
            'shift_type' => 'diurno',
            'start_time' => now()->subHours(2),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-TEST-1',
        ]);

        BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $user->id,
            'product_id'               => $product->id,
            'machine_id'               => $machine->id,
            'quantity'                 => 1,
            'weight'                   => 20.0,
            'original_weight'          => 20.0,
            'waste_weight_kg'          => 0,
            'is_waste_record'          => false,
            'recorded_at'              => now(),
            'status'                   => 'pending_review',
            'is_printed'               => true,
            'weight_quality_grade'     => 'B',
            'weight_deviation_percent' => 0,
            'completed_packages_count' => 1,
            'fractional_units'         => 0,
            'is_package_completed'     => true,
            'labor_earned_amount'      => 3.0,
            'labor_retained_amount'    => 0,
            'qr_code'                  => 'TEST-QR',
            'sync_id'                  => 'PROD-TEST-1',
        ]);

        $mat = RawMaterial::create([
            'name'       => 'Resina 3003',
            'code'       => '3003',
            'cost_per_kg'=> 1.5,
            'is_active'  => true,
        ]);

        // Ejecutar comando Artisan
        $this->artisan('factory:reset-tests', ['--force' => true])
            ->assertExitCode(0);

        // Validar que se vaciaron producciones y turnos
        $this->assertDatabaseCount('bag_productions', 0);
        $this->assertDatabaseCount('bag_shifts', 0);

        // Validar que los usuarios, productos y materias primas siguen intactos
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('bag_products', ['id' => $product->id]);
        $this->assertDatabaseHas('raw_materials', ['id' => $mat->id]);

        // Validar que los stocks de materias primas quedaron en 25 Kg (1 saco) en Planta y 250 Kg en Principal
        $this->assertDatabaseHas('raw_material_stocks', [
            'raw_material_id' => $mat->id,
            'warehouse_type'  => 'planta',
            'total_kg'        => 25.00,
            'sacos_count'     => 1.0,
        ]);

        $this->assertDatabaseHas('raw_material_stocks', [
            'raw_material_id' => $mat->id,
            'warehouse_type'  => 'principal',
            'total_kg'        => 250.00,
            'sacos_count'     => 10.0,
        ]);
    }
}
